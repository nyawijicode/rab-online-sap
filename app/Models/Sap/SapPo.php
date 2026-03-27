<?php

namespace App\Models\Sap;

class SapPo
{
    public int $DocEntry;
    public int $DocNum;
    public string $CardCode;
    public string $CardName;
    public string $DocDate;
    public string $DocDueDate;
    public float $DocTotal;
    public string $DocStatus;
    public ?string $Comments = null;

    /** @var SapPoDetail[] */
    public array $details = [];

    public static function fromArray(array $row): self
    {
        $self = new self();

        $self->DocEntry   = (int) ($row['DOCENTRY'] ?? $row['DocEntry'] ?? 0);
        $self->DocNum     = (int) ($row['DOCNUM'] ?? $row['DocNum'] ?? 0);
        $self->CardCode   = (string) ($row['CARDCODE'] ?? $row['CardCode'] ?? '');
        $self->CardName   = (string) ($row['CARDNAME'] ?? $row['CardName'] ?? '');
        $self->DocDate    = (string) ($row['DOCDATE'] ?? $row['DocDate'] ?? '');
        $self->DocDueDate = (string) ($row['DOCDUEDATE'] ?? $row['DocDueDate'] ?? '');
        $self->DocTotal   = (float) ($row['DOCTOTAL'] ?? $row['DocTotal'] ?? 0);
        $self->DocStatus  = (string) ($row['DOCSTATUS'] ?? $row['DocStatus'] ?? '');
        $self->Comments   = $row['COMMENTS'] ?? $row['Comments'] ?? null;

        return $self;
    }

    public function toArray(): array
    {
        return [
            'DocEntry'   => $this->DocEntry,
            'DocNum'     => $this->DocNum,
            'CardCode'   => $this->CardCode,
            'CardName'   => $this->CardName,
            'DocDate'    => $this->DocDate,
            'DocDueDate' => $this->DocDueDate,
            'DocTotal'   => $this->DocTotal,
            'DocStatus'  => $this->DocStatus,
            'Comments'   => $this->Comments,
        ];
    }
}
