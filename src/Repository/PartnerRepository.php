<?php

namespace App\Repository;

use App\Core\Database;
use App\Domain\Partners\Partner;
use App\Domain\Partners\PartnerCustomField;
use App\Domain\Partners\PartnerEmployee;
use PDO;

class PartnerRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return Partner[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM partners ORDER BY name ASC');

        return array_map([Partner::class, 'fromRow'], $stmt->fetchAll());
    }

    public function find(int $id): ?Partner
    {
        $stmt = $this->db->prepare('SELECT * FROM partners WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Partner::fromRow($row) : null;
    }

    public function create(Partner $partner): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO partners (
                name, type, tax_number, address_line1, address_line2, postal_code, city, country,
                phone, fax, mobile, email, website, bank_account, vat_number, iban, swift, timocom_id, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute($this->paramsFrom($partner));

        return (int) $this->db->lastInsertId();
    }

    public function update(Partner $partner): void
    {
        $stmt = $this->db->prepare(
            'UPDATE partners SET
                name = ?, type = ?, tax_number = ?, address_line1 = ?, address_line2 = ?, postal_code = ?, city = ?, country = ?,
                phone = ?, fax = ?, mobile = ?, email = ?, website = ?, bank_account = ?, vat_number = ?, iban = ?, swift = ?, timocom_id = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([...$this->paramsFrom($partner), $partner->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM partners WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return PartnerEmployee[] */
    public function employeesFor(int $partnerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM partner_employees WHERE partner_id = ? ORDER BY name ASC');
        $stmt->execute([$partnerId]);

        return array_map([PartnerEmployee::class, 'fromRow'], $stmt->fetchAll());
    }

    public function addEmployee(PartnerEmployee $employee): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO partner_employees (partner_id, name, job_title, phone, email) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $employee->partnerId,
            $employee->name,
            $employee->jobTitle,
            $employee->phone,
            $employee->email,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function deleteEmployee(int $employeeId, int $partnerId): void
    {
        $stmt = $this->db->prepare('DELETE FROM partner_employees WHERE id = ? AND partner_id = ?');
        $stmt->execute([$employeeId, $partnerId]);
    }

    /** @return PartnerCustomField[] */
    public function customFieldsFor(int $partnerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM partner_custom_fields WHERE partner_id = ? ORDER BY field_key ASC');
        $stmt->execute([$partnerId]);

        return array_map([PartnerCustomField::class, 'fromRow'], $stmt->fetchAll());
    }

    public function setCustomField(int $partnerId, string $key, ?string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO partner_custom_fields (partner_id, field_key, field_value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)'
        );
        $stmt->execute([$partnerId, $key, $value]);
    }

    public function deleteCustomField(int $fieldId, int $partnerId): void
    {
        $stmt = $this->db->prepare('DELETE FROM partner_custom_fields WHERE id = ? AND partner_id = ?');
        $stmt->execute([$fieldId, $partnerId]);
    }

    /** @return array<int, string> */
    private function paramsFrom(Partner $partner): array
    {
        return [
            $partner->name,
            $partner->type,
            $partner->taxNumber,
            $partner->addressLine1,
            $partner->addressLine2,
            $partner->postalCode,
            $partner->city,
            $partner->country,
            $partner->phone,
            $partner->fax,
            $partner->mobile,
            $partner->email,
            $partner->website,
            $partner->bankAccount,
            $partner->vatNumber,
            $partner->iban,
            $partner->swift,
            $partner->timocomId,
            $partner->isActive ? 1 : 0,
        ];
    }
}
