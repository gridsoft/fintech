<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Payroll\Employee;
use PDO;

class EmployeeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Employee[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM employees ORDER BY name ASC');

        return array_map([Employee::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Employee[] */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM employees WHERE is_active = 1 ORDER BY name ASC');

        return array_map([Employee::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Employee
    {
        $stmt = $this->db->prepare('SELECT * FROM employees WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Employee::fromRow($row) : null;
    }

    public function create(Employee $employee): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employees (name, embg, hire_date, prior_staz_months, termination_date, base_gross_salary, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $employee->name,
            $employee->embg,
            $employee->hireDate,
            $employee->priorStazMonths,
            $employee->terminationDate,
            $employee->baseGrossSalary,
            $employee->isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(Employee $employee): void
    {
        $stmt = $this->db->prepare(
            'UPDATE employees SET name = ?, embg = ?, hire_date = ?, prior_staz_months = ?, termination_date = ?, base_gross_salary = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $employee->name,
            $employee->embg,
            $employee->hireDate,
            $employee->priorStazMonths,
            $employee->terminationDate,
            $employee->baseGrossSalary,
            $employee->isActive ? 1 : 0,
            $employee->id,
        ]);
    }
}
