<?php

namespace App\Service;

use App\Core\Database;
use App\Domain\Partners\Partner;
use App\Domain\Partners\PartnerEmployee;
use App\Repository\PartnerRepository;
use PDO;
use Throwable;

/**
 * На create-формата вработените и дополнителните полиња се внесуваат во
 * динамични редови (исто како ставки на фактура) и се испраќаат заедно со
 * главната форма, бидејќи партнерот сè уште нема id пред да се зачува.
 * Ова ги создава партнерот + сите вработени + сите полиња во една
 * транзакција. На edit-формата секое додавање си остана посебна, веднашна
 * акција (постоечкото однесување) — таму партнерот веќе постои.
 */
class PartnerService
{
    private PDO $db;
    private PartnerRepository $partners;

    public function __construct(?PartnerRepository $partners = null)
    {
        $this->db = Database::connection();
        $this->partners = $partners ?? new PartnerRepository();
    }

    /**
     * @param array<int, array{name: string, job_title?: ?string, phone?: ?string, email?: ?string}> $employees
     * @param array<int, array{key: string, value?: ?string}> $customFields
     */
    public function createPartner(Partner $partner, array $employees, array $customFields): int
    {
        $this->db->beginTransaction();

        try {
            $partnerId = $this->partners->create($partner);

            foreach ($employees as $employee) {
                $this->partners->addEmployee(new PartnerEmployee(
                    $partnerId,
                    $employee['name'],
                    $employee['job_title'] ?? null,
                    $employee['phone'] ?? null,
                    $employee['email'] ?? null
                ));
            }

            foreach ($customFields as $field) {
                $this->partners->setCustomField($partnerId, $field['key'], $field['value'] ?? null);
            }

            $this->db->commit();

            return $partnerId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
