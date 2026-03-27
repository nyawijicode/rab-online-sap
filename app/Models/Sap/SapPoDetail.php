<?php

namespace App\Models\Sap;

class SapPoDetail
{
    public int $DocEntry;
    public int $LineNum;
    public string $ItemCode;
    public string $Dscription;
    public float $Quantity;
    public float $Price;
    public float $LineTotal;
    public string $WhsCode;

    public static function fromArray(array $row): self
    {
        $self = new self();

        $self->DocEntry  = (int) ($row['DOCENTRY'] ?? $row['DocEntry'] ?? 0);
        $self->LineNum   = (int) ($row['LINENUM'] ?? $row['LineNum'] ?? 0);
        $self->ItemCode  = (string) ($row['ITEMCODE'] ?? $row['ItemCode'] ?? '');
        $self->Dscription = (string) ($row['DSCRIPTION'] ?? $row['Dscription'] ?? '');
        $self->Quantity  = (float) ($row['QUANTITY'] ?? $row['Quantity'] ?? 0);
        $self->Price     = (float) ($row['PRICE'] ?? $row['Price'] ?? 0);
        $self->LineTotal = (float) ($row['LINETOTAL'] ?? $row['LineTotal'] ?? 0);
        $self->WhsCode   = (string) ($row['WHSCODE'] ?? $row['WhsCode'] ?? '');

        return $self;
    }

    public function toArray(): array
    {
        return [
            'DocEntry'  => $this->DocEntry,
            'LineNum'   => $this->LineNum,
            'ItemCode'  => $this->ItemCode,
            'Dscription'=> $this->Dscription,
            'Quantity'  => $this->Quantity,
            'Price'     => $this->Price,
            'LineTotal' => $this->LineTotal,
            'WhsCode'   => $this->WhsCode,
        ];
    }
}
