<?php

namespace App\Services\Sap;

class SapHanaService
{
    public function __construct(
        protected HanaOdbcConnector $connector
    ) {}

    /**
     * SEMUA Purchase Order (tanpa TOP/LIMIT).
     * Optional filter:
     *   - $docStatus    : 'O' (open) / 'C' (closed)
     *   - $statusPickup2: 'Y' / 'N' — berdasarkan UDF U_SOL_STATUS_PICKUP2 di OPOR
     */
    public function getPurchaseOrders(?string $docStatus = null, ?string $statusPickup2 = null): array
    {
        $sql = <<<SQL
SELECT
    OP."DocEntry",
    OP."DocNum",
    OP."CardCode",
    OP."CardName",
    OP."DocDate",
    OP."DocDueDate",
    OP."DocTotal",
    OP."DocStatus",
    OP."Comments",
    OP."U_SOL_STATUS_PICKUP2" AS "StatusPickup2"
FROM "SAP"."OPOR" OP
%s
ORDER BY OP."DocEntry" DESC
SQL;

        $conditions = [];
        $bindings   = [];

        if ($docStatus !== null && $docStatus !== '') {
            $conditions[] = 'OP."DocStatus" = ?';
            $bindings[]   = $docStatus;
        }

        if ($statusPickup2 !== null && $statusPickup2 !== '') {
            $conditions[] = 'OP."U_SOL_STATUS_PICKUP2" = ?';
            $bindings[]   = $statusPickup2;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql   = sprintf($sql, $where);

        return $this->connector->select($sql, $bindings);
    }

    /**
     * Ambil SEMUA line PO dari POR1 untuk 1 DocEntry.
     */
    public function getPurchaseOrderLines(int $docEntry): array
    {
        $sql = <<<SQL
SELECT
    P."DocEntry",
    P."LineNum",
    P."ItemCode",
    P."Dscription",
    P."Quantity",
    P."Price",
    P."LineTotal",
    P."WhsCode",
    P."Project"
FROM "SAP"."POR1" P
WHERE P."DocEntry" = ?
ORDER BY P."LineNum"
SQL;

        return $this->connector->select($sql, [$docEntry]);
    }

    /**
     * Header + detail 1 PO.
     */
    public function getPurchaseOrderDetail(int $docEntry): array
    {
        $headerSql = <<<SQL
SELECT
    OP."DocEntry",
    OP."DocNum",
    OP."CardCode",
    OP."CardName",
    OP."DocDate",
    OP."DocDueDate",
    OP."DocTotal",
    OP."DocStatus",
    OP."Comments"
FROM "SAP"."OPOR" OP
WHERE OP."DocEntry" = ?
SQL;

        $header = $this->connector->select($headerSql, [$docEntry]);
        $lines  = $this->getPurchaseOrderLines($docEntry);

        return [
            'header' => $header[0] ?? null,
            'lines'  => $lines,
        ];
    }

    /**
     * Ambil Comments dari OPOR (kalau kamu pakai sebagai PackageId).
     */
    public function getPurchaseOrderPackageId(int $docEntry): string
    {
        $sql = <<<SQL
SELECT
    OP."Comments"
FROM "SAP"."OPOR" OP
WHERE OP."DocEntry" = ?
SQL;

        $rows = $this->connector->select($sql, [$docEntry]);

        return trim((string) ($rows[0]['Comments'] ?? ''));
    }

    /**
     * Ambil SEMUA Business Partner dari OCRD (tanpa filter CardType).
     */
    public function getBusinessPartners(): array
    {
        $sql = <<<SQL
SELECT
    C."CardCode",
    C."CardName",
    C."CardType",
    C."GroupCode",
    C."Phone1",
    C."Phone2",
    C."Cellular",
    C."Fax",
    C."E_Mail"     AS "Email",
    C."IntrntSite",
    C."CntctPrsn",
    C."Balance",
    C."Currency",
    C."Address",
    C."ZipCode",
    C."City",
    C."County",
    C."Country",
    C."Notes",
    C."CreateDate",
    C."UpdateDate"
FROM "SAP"."OCRD" C
ORDER BY C."CardCode"
SQL;

        return $this->connector->select($sql);
    }

    /**
     * Vendor (Supplier) saja: OCRD CardType = 'S'
     */
    public function getVendors(): array
    {
        $sql = <<<SQL
SELECT
    C."CardCode",
    C."CardName",
    C."CardType",
    C."GroupCode",
    C."Phone1",
    C."Phone2",
    C."Cellular",
    C."Fax",
    C."E_Mail"     AS "Email",
    C."IntrntSite",
    C."CntctPrsn",
    C."Balance",
    C."Currency",
    C."Address",
    C."ZipCode",
    C."City",
    C."County",
    C."Country",
    C."Notes",
    C."CreateDate",
    C."UpdateDate"
FROM "SAP"."OCRD" C
WHERE C."CardType" = 'S'
ORDER BY C."CardCode"
SQL;

        return $this->connector->select($sql);
    }

    /**
     * Detail 1 BP berdasarkan CardCode (OCRD).
     */
    public function getVendorByCode(string $cardCode): ?array
    {
        $sql = <<<SQL
SELECT
    C."CardCode",
    C."CardName",
    C."CardType",
    C."GroupCode",
    C."Phone1",
    C."Phone2",
    C."Cellular",
    C."Fax",
    C."E_Mail"     AS "Email",
    C."IntrntSite",
    C."CntctPrsn",
    C."Balance",
    C."Currency",
    C."Address",
    C."ZipCode",
    C."City",
    C."County",
    C."Country",
    C."Notes",
    C."CreateDate",
    C."UpdateDate"
FROM "SAP"."OCRD" C
WHERE C."CardCode" = ?
SQL;

        $rows = $this->connector->select($sql, [$cardCode]);

        return $rows[0] ?? null;
    }

    /**
     * Vendor Ekspedisi: Business Partner di OCRD dengan CardCode diawali 'VE'
     */
    public function getVendorsEkspedisi(): array
    {
        $sql = <<<SQL
SELECT
    BP."CardCode",
    BP."CardName",
    BP."CardType",
    BP."GroupCode",
    GR."GroupName",
    BP."LicTradNum",
    BP."Phone1",
    BP."Phone2",
    BP."Cellular",
    BP."Fax",
    BP."E_Mail"    AS "Email",
    BP."IntrntSite",
    BP."CntctPrsn",
    BP."Balance",
    BP."Currency",
    BP."Address",
    BP."ZipCode",
    BP."City",
    BP."County",
    BP."Country",
    BP."Notes",
    BP."CreateDate",
    BP."UpdateDate"
FROM "SAP"."OCRD" AS BP
LEFT JOIN "SAP"."OCRG" AS GR
    ON GR."GroupCode" = BP."GroupCode"
WHERE BP."CardCode" LIKE 'VE%'
ORDER BY BP."CardCode"
SQL;

        return $this->connector->select($sql);
    }

    /**
     * List semua Quality Check (header saja).
     */
    public function getQualityChecks(): array
    {
        $sql = <<<SQL
SELECT
    H."DocEntry"                             AS "DocEntry",
    H."DocNum"                               AS "QCNo",
    H."U_GRPO_NO"                            AS "GrpoNo",
    H."U_BRANCH"                             AS "Branch",
    H."CreateDate"                           AS "QCDate",
    (SELECT STRING_AGG(CAST(D."U_ITEMCODE" AS VARCHAR), ', ') FROM "SAP"."@SOL_QC_D1" D WHERE D."DocEntry" = H."DocEntry") AS "ItemCodes",
    (SELECT STRING_AGG(CAST(D."U_ITEMNAME" AS VARCHAR), ', ') FROM "SAP"."@SOL_QC_D1" D WHERE D."DocEntry" = H."DocEntry") AS "ItemNames",
    CASE H."Status"
        WHEN 'O' THEN 'Open'
        WHEN 'C' THEN 'Closed'
        WHEN 'X' THEN 'Canceled'
        ELSE H."Status"
    END                                      AS "Status"
FROM "SAP"."@SOL_QC_H" H
ORDER BY H."DocEntry" DESC
SQL;

        return $this->connector->select($sql);
    }

    /**
     * Detail 1 QC (header + detail + serial).
     * PERBAIKAN: pakai binding, jangan inject {$docEntry}.
     */
    public function getQualityCheckDetail(int $docEntry): array
    {
        $headerSql = <<<SQL
SELECT
    H.*
FROM "SAP"."@SOL_QC_H" H
WHERE H."DocEntry" = ?
SQL;

        $detailSql = <<<SQL
SELECT
    D.*
FROM "SAP"."@SOL_QC_D1" D
WHERE D."DocEntry" = ?
ORDER BY D."LineId"
SQL;

        $serialSql = <<<SQL
SELECT
    S.*
FROM "SAP"."@SOL_QC_D2" S
WHERE S."DocEntry" = ?
ORDER BY S."LineId"
SQL;

        $header  = $this->connector->select($headerSql, [$docEntry]);
        $details = $this->connector->select($detailSql, [$docEntry]);
        $serials = $this->connector->select($serialSql, [$docEntry]);

        return [
            'header'  => $header[0] ?? null,
            'details' => $details,
            'serials' => $serials,
        ];
    }

    /**
     * List semua Project (OPRJ) dari SAP.
     */
    public function getProjects(): array
    {
        $sql = <<<SQL
SELECT
    P."PrjCode",
    P."PrjName",
    P."ValidFrom",
    P."ValidTo",
    P."Active"
FROM "SAP"."OPRJ" P
ORDER BY P."PrjCode"
SQL;

        return $this->connector->select($sql);
    }

    /**
     * Detail satu Project berdasarkan PrjCode.
     */
    public function getProjectByCode(string $projectCode): ?array
    {
        $sql = <<<SQL
SELECT
    P."PrjCode",
    P."PrjName",
    P."ValidFrom",
    P."ValidTo",
    P."Active"
FROM "SAP"."OPRJ" P
WHERE P."PrjCode" = ?
SQL;

        $rows = $this->connector->select($sql, [$projectCode]);

        return $rows[0] ?? null;
    }

    /**
     * Semua A/P Invoice (OPCH).
     */
    public function getApInvoices(): array
    {
        $sql = <<<SQL
SELECT
    OP."DocEntry",
    OP."DocNum",
    OP."CardCode",
    OP."CardName",
    OP."DocDate",
    OP."DocDueDate",
    OP."DocTotal",
    OP."DocStatus",
    OP."Comments",
    OP."U_SOL_FP_FULL_NO" AS "FakturPajak"
FROM "SAP"."OPCH" OP
ORDER BY OP."DocEntry" DESC
SQL;

        return $this->connector->select($sql);
    }
}
