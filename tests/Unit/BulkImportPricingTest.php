<?php

namespace Tests\Unit;

use App\CPU\BulkImportHelper;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic coverage for the price handling that enables enquiry-based (price-less) products.
 *
 * Requirements exercised:
 *  - Price fields are optional; a blank price imports as NULL (never 0).
 *  - Only genuinely invalid price text fails; a blank cell is accepted.
 *  - Products with valid prices keep working (numeric values preserved / computed).
 *  - The "price" header alias maps onto unit_price.
 */
class BulkImportPricingTest extends TestCase
{
    private array $defaults = ['rounding' => 'none', 'tax' => 0, 'stock' => 0, 'refundable' => 0];

    public function test_valid_prices_are_kept_as_numbers()
    {
        $result = BulkImportHelper::computePricing(
            ['unit_price' => '120', 'purchase_price' => '90', 'allow_below' => true],
            $this->defaults
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(120.0, $result['unit_price']);
        $this->assertSame(90.0, $result['purchase_price']);
    }

    public function test_blank_prices_become_null_not_zero()
    {
        $result = BulkImportHelper::computePricing(
            ['unit_price' => '', 'purchase_price' => '', 'supplier_price' => ''],
            $this->defaults
        );

        $this->assertArrayNotHasKey('error', $result, 'A blank price must not fail the row.');
        $this->assertNull($result['unit_price'], 'Blank selling price must be NULL.');
        $this->assertNull($result['purchase_price'], 'Blank purchase price must be NULL.');
        // Explicitly guard against the old "coerce to 0" behaviour.
        $this->assertNotSame(0.0, $result['unit_price']);
        $this->assertNotSame(0.0, $result['purchase_price']);
    }

    public function test_missing_price_columns_become_null()
    {
        $result = BulkImportHelper::computePricing(['name' => 'Widget'], $this->defaults);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertNull($result['unit_price']);
        $this->assertNull($result['purchase_price']);
        $this->assertFalse($result['unit_below_purchase']);
    }

    public function test_invalid_unit_price_text_fails()
    {
        $result = BulkImportHelper::computePricing(['unit_price' => 'call us'], $this->defaults);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_invalid_purchase_price_text_fails()
    {
        $result = BulkImportHelper::computePricing(['purchase_price' => 'N/A'], $this->defaults);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_unit_price_computed_from_supplier_and_margin()
    {
        $result = BulkImportHelper::computePricing(
            ['supplier_price' => '100', 'exchange_rate' => '1', 'margin_percent' => '20', 'allow_below' => true],
            $this->defaults
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(100.0, $result['purchase_price']);
        $this->assertSame(120.0, $result['unit_price']);
    }

    public function test_price_header_alias_maps_to_unit_price()
    {
        $mapped = BulkImportHelper::mapRow(['Name' => 'Relay', 'Price' => '55']);
        $this->assertArrayHasKey('unit_price', $mapped);
        $this->assertSame('55', $mapped['unit_price']);
    }
}
