<?php

namespace App\DTOs;

class RhUser
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $email,
        public readonly string $name,
        public readonly ?string $puesto = null,
        public readonly ?string $departamento = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?string $fechaIngreso = null,
        public readonly ?string $formacion = null,
        public readonly ?string $gerenciaRegional = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: (string) ($data['employee_id'] ?? $data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            puesto: $data['puesto'] ?? null,
            departamento: $data['departamento'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            fechaIngreso: $data['fecha_ingreso'] ?? null,
            formacion: $data['formacion'] ?? null,
            gerenciaRegional: $data['gerencia_regional'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'email' => $this->email,
            'name' => $this->name,
            'puesto' => $this->puesto,
            'departamento' => $this->departamento,
            'avatar_url' => $this->avatarUrl,
            'fecha_ingreso' => $this->fechaIngreso,
            'formacion' => $this->formacion,
            'gerencia_regional' => $this->gerenciaRegional,
        ];
    }
}
