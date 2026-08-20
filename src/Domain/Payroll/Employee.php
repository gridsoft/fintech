<?php

namespace App\Domain\Payroll;

class Employee
{
    public ?int $id;
    public string $name;
    public ?string $embg;
    public string $hireDate;
    public int $priorStazMonths;
    public ?string $terminationDate;
    public string $baseGrossSalary;
    public bool $isActive;

    public function __construct(
        string $name,
        ?string $embg,
        string $hireDate,
        int $priorStazMonths,
        ?string $terminationDate,
        string $baseGrossSalary,
        bool $isActive = true,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->embg = $embg;
        $this->hireDate = $hireDate;
        $this->priorStazMonths = $priorStazMonths;
        $this->terminationDate = $terminationDate;
        $this->baseGrossSalary = $baseGrossSalary;
        $this->isActive = $isActive;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            $row['name'],
            $row['embg'],
            $row['hire_date'],
            (int) $row['prior_staz_months'],
            $row['termination_date'],
            $row['base_gross_salary'],
            (bool) $row['is_active'],
            (int) $row['id']
        );
    }
}
