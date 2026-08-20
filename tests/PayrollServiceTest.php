<?php

namespace Tests;

use App\Core\Database;
use App\Domain\Payroll\Employee;
use App\Repository\EmployeeRepository;
use App\Repository\PayrollRepository;
use App\Service\PayrollService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class PayrollServiceTest extends TestCase
{
    private PDO $db;
    private EmployeeRepository $employees;
    private PayrollRepository $payroll;
    private PayrollService $service;

    private array $employeeIds = [];

    protected function setUp(): void
    {
        $this->db = Database::connection();
        $this->employees = new EmployeeRepository();
        $this->payroll = new PayrollRepository();
        $this->service = new PayrollService($this->employees, $this->payroll);
    }

    protected function tearDown(): void
    {
        if (!$this->employeeIds) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($this->employeeIds), '?'));

        $runIds = $this->db->prepare("SELECT DISTINCT payroll_run_id FROM payslips WHERE employee_id IN ($placeholders)");
        $runIds->execute($this->employeeIds);
        $runIds = $runIds->fetchAll(PDO::FETCH_COLUMN);

        $this->db->prepare("DELETE FROM payslips WHERE employee_id IN ($placeholders)")->execute($this->employeeIds);

        // runPayroll() намерно работи глобално врз сите активни вработени (реален
        // месечен процес) — еден заеднички запис може да содржи и НЕ-тест
        // вработени (пр. реални податоци внесени преку живата апликација). Затоа
        // го бришеме заедничкиот payroll_runs/journal запис САМО ако по бришењето
        // на нашите платни листи не остана НИТУ ЕДНА друга платна листа за него.
        foreach ($runIds as $runId) {
            $remaining = $this->db->prepare('SELECT COUNT(*) FROM payslips WHERE payroll_run_id = ?');
            $remaining->execute([$runId]);

            if ((int) $remaining->fetchColumn() > 0) {
                continue;
            }

            $entryId = $this->db->prepare('SELECT journal_entry_id FROM payroll_runs WHERE id = ?');
            $entryId->execute([$runId]);
            $entryId = $entryId->fetchColumn();

            $this->db->prepare('DELETE FROM payroll_runs WHERE id = ?')->execute([$runId]);
            $this->db->prepare('DELETE FROM journal_lines WHERE journal_entry_id = ?')->execute([$entryId]);
            $this->db->prepare('DELETE FROM journal_entries WHERE id = ?')->execute([$entryId]);
        }

        $this->db->prepare("DELETE FROM employees WHERE id IN ($placeholders)")->execute($this->employeeIds);
    }

    private function makeEmployee(string $hireDate, ?string $terminationDate, string $grossSalary, int $priorStazMonths = 0): int
    {
        $id = $this->employees->create(new Employee(
            'Тест вработен ' . uniqid(),
            null,
            $hireDate,
            $priorStazMonths,
            $terminationDate,
            $grossSalary
        ));
        $this->employeeIds[] = $id;

        return $id;
    }

    public function test_run_payroll_posts_balanced_entry_and_creates_payslips(): void
    {
        $employeeId = $this->makeEmployee('2000-01-01', null, '30000.00');

        $result = $this->service->runPayroll('2000-03-31');

        $this->assertGreaterThanOrEqual(1, $result['count']);

        $stmt = $this->db->prepare('SELECT * FROM payslips WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $payslip = $stmt->fetch();

        $this->assertNotFalse($payslip, 'платната листа треба да е создадена');
        // 18.4% + 7.5% + 1.2% = 27.1% од 30000 = 8130.00
        $this->assertSame('5520.00', $payslip['pension_contribution']);
        $this->assertSame('2250.00', $payslip['health_contribution']);
        $this->assertSame('360.00', $payslip['employment_contribution']);
        // основа за данок = 30000 - 8130 - 11463 = 10407.00; данок 10% = 1040.70
        $this->assertSame('10407.00', $payslip['taxable_base']);
        $this->assertSame('1040.70', $payslip['pit']);
        // нето = 30000 - 8130 - 1040.70 = 20829.30
        $this->assertSame('20829.30', $payslip['net_salary']);

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d, COALESCE(SUM(credit), 0) AS c FROM journal_lines WHERE journal_entry_id = (SELECT journal_entry_id FROM payroll_runs WHERE period_date = ?)');
        $stmt->execute(['2000-03-31']);
        $sums = $stmt->fetch();
        $this->assertEquals((float) $sums['d'], (float) $sums['c']);
        $this->assertGreaterThanOrEqual(30000.00, (float) $sums['d']);
    }

    public function test_running_payroll_twice_for_same_period_is_idempotent(): void
    {
        $employeeId = $this->makeEmployee('2000-01-01', null, '25000.00');

        $this->service->runPayroll('2000-04-30');
        $second = $this->service->runPayroll('2000-04-30');

        $this->assertSame(0, $second['count']);

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM payslips WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_run_payroll_skips_employees_not_yet_hired_or_terminated(): void
    {
        $notYetHired = $this->makeEmployee('2000-06-01', null, '25000.00');
        $terminated = $this->makeEmployee('1994-01-01', '2000-04-30', '25000.00');
        $eligible = $this->makeEmployee('1994-01-01', null, '25000.00');

        $this->service->runPayroll('2000-05-31');

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM payslips WHERE employee_id = ?');

        $stmt->execute([$notYetHired]);
        $this->assertSame(0, (int) $stmt->fetchColumn(), 'вработен со датум на вработување по периодот мора да биде исклучен');

        $stmt->execute([$terminated]);
        $this->assertSame(0, (int) $stmt->fetchColumn(), 'вработен престанат пред периодот мора да биде исклучен');

        $stmt->execute([$eligible]);
        $this->assertSame(1, (int) $stmt->fetchColumn(), 'активен вработен во периодот мора да биде вклучен');
    }

    public function test_taxable_base_floors_at_zero_when_below_personal_exemption(): void
    {
        // Бруто 10000: придонеси = 27.1% * 10000 = 2710.00; 10000 - 2710 = 7290, под 11463 -> основа 0.
        $employeeId = $this->makeEmployee('2000-01-01', null, '10000.00');

        $this->service->runPayroll('2000-07-31');

        $stmt = $this->db->prepare('SELECT taxable_base, pit, net_salary FROM payslips WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $payslip = $stmt->fetch();

        $this->assertSame('0.00', $payslip['taxable_base']);
        $this->assertSame('0.00', $payslip['pit']);
        $this->assertSame('7290.00', $payslip['net_salary']);
    }

    public function test_seniority_supplement_is_added_to_gross_before_contributions_and_tax(): void
    {
        // 24 месеци признат стаж пред вработување + вработен точно на датумот
        // на периодот (0 дополнителни месеци кај оваа фирма) = точно 2 години
        // вкупен стаж -> додаток 2 * 0.5% = 1% од основната плата.
        $employeeId = $this->makeEmployee('2000-09-30', null, '20000.00', 24);

        $this->service->runPayroll('2000-09-30');

        $stmt = $this->db->prepare('SELECT * FROM payslips WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $payslip = $stmt->fetch();

        $this->assertSame('20000.00', $payslip['base_salary']);
        $this->assertSame(24, (int) $payslip['seniority_months']);
        $this->assertSame('200.00', $payslip['seniority_supplement'], '2 години * 0.5% * 20000 = 200.00');
        $this->assertSame('20200.00', $payslip['gross_salary'], 'бруто = основна + додаток за стаж');
        $this->assertSame('3716.80', $payslip['pension_contribution'], 'придонесите се сметаат од БРУТО (со додатокот), не од основната плата');
        $this->assertSame('1515.00', $payslip['health_contribution']);
        $this->assertSame('242.40', $payslip['employment_contribution']);
        $this->assertSame('3262.80', $payslip['taxable_base']);
        $this->assertSame('326.28', $payslip['pit']);
        $this->assertSame('14399.52', $payslip['net_salary']);

        // Заеднички запис за периодот — може да содржи и други (не-тест) активни
        // вработени, затоа проверуваме "барем" нашиот придонес, не точна вредност.
        $stmt = $this->db->prepare('SELECT total_seniority_supplement FROM payroll_runs WHERE period_date = ?');
        $stmt->execute(['2000-09-30']);
        $run = $stmt->fetch();
        $this->assertGreaterThanOrEqual(200.00, (float) $run['total_seniority_supplement']);

        $stmt = $this->db->prepare('SELECT COALESCE(SUM(debit), 0) AS d FROM journal_lines WHERE journal_entry_id = (SELECT journal_entry_id FROM payroll_runs WHERE period_date = ?) AND debit > 0');
        $stmt->execute(['2000-09-30']);
        $this->assertGreaterThanOrEqual(20200.00, (float) $stmt->fetchColumn(), 'бруто трошокот книжен на дебит страна мора да го вклучи додатокот за стаж');
    }

    public function test_sick_shift_and_holiday_days_adjust_gross_before_contributions_and_tax(): void
    {
        // Бруто 21000 / делител 21 = дневна стапка точно 1000.00 (погоден избор
        // за чисти бројки). Боледување 70% исплата -> одбиток 30%/ден.
        $employeeId = $this->makeEmployee('2000-10-31', null, '21000.00');

        $this->service->runPayroll('2000-10-31', [
            $employeeId => ['sick_days' => 2, 'shift_days' => 3, 'holiday_days' => 1],
        ]);

        $stmt = $this->db->prepare('SELECT * FROM payslips WHERE employee_id = ?');
        $stmt->execute([$employeeId]);
        $payslip = $stmt->fetch();

        $this->assertSame('1000.00', $payslip['daily_rate']);
        $this->assertSame(2, (int) $payslip['sick_days']);
        $this->assertSame('600.00', $payslip['sick_deduction'], '2 дена * 30% (100-70) * 1000 = 600.00');
        $this->assertSame(3, (int) $payslip['shift_days']);
        $this->assertSame('150.00', $payslip['shift_supplement'], '3 дена * 5% * 1000 = 150.00');
        $this->assertSame(1, (int) $payslip['holiday_days']);
        $this->assertSame('500.00', $payslip['holiday_supplement'], '1 ден * 50% * 1000 = 500.00');
        // бруто = 21000 - 600 + 150 + 500 = 21050.00
        $this->assertSame('21050.00', $payslip['gross_salary']);
        $this->assertSame('3873.20', $payslip['pension_contribution']);
        $this->assertSame('1578.75', $payslip['health_contribution']);
        $this->assertSame('252.60', $payslip['employment_contribution']);
        $this->assertSame('3882.45', $payslip['taxable_base']);
        $this->assertSame('388.24', $payslip['pit']);
        $this->assertSame('14957.21', $payslip['net_salary']);
    }

    public function test_gross_never_goes_negative_even_with_excessive_sick_days(): void
    {
        // Сервисот не го ограничува бројот денови (тоа е валидациона грешка на
        // контролерот) — 100 боледувачки денови би го „превртиле" бруто во
        // негативно без експлицитниот под во сервисот. Со само еден (тест)
        // вработен опфатен во периодот, вкупното бруто паѓа на точно 0 -> јасна
        // грешка наместо матна пропагирана од LedgerService.
        $employeeId = $this->makeEmployee('2000-11-30', null, '10000.00');

        $this->expectException(InvalidArgumentException::class);

        $this->service->runPayroll('2000-11-30', [
            $employeeId => ['sick_days' => 100],
        ]);
    }
}
