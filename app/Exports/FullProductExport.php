<?php

namespace App\Exports;

use App\CPU\BackEndHelper;
use App\Model\Category;
use App\Model\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Full product export. Columns match the bulk-import headers (so the file can be
 * edited and re-imported) plus human-readable reference columns. Read-only.
 *
 * FromQuery chunks the read so the whole products table is never loaded at once;
 * formatting (widths, freeze, filter, alignment) is applied in the AfterSheet event.
 */
class FullProductExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle
{
    /** @var \Illuminate\Support\Collection category id => name lookup, loaded once */
    private $category_names;

    /** Last column letter of the sheet (kept in sync with headings()). */
    private const LAST_COLUMN = 'AG';

    public function __construct()
    {
        $this->category_names = Category::pluck('name', 'id');
    }

    public function query()
    {
        return Product::query()->with('brand')->orderBy('id');
    }

    public function title(): string
    {
        return 'Products';
    }

    public function headings(): array
    {
        return [
            'id', 'name', 'slug', 'product_code', 'brand_id', 'brand_name',
            'category_id', 'category_name', 'sub_category_id', 'sub_category_name',
            'sub_sub_category_id', 'sub_sub_category_name', 'purchase_price', 'unit_price',
            'selling_price', 'tax', 'discount', 'discount_type', 'current_stock', 'min_qty',
            'unit', 'refundable', 'status_text', 'featured_text', 'details_html', 'details_plain_text',
            'meta_title', 'meta_description', 'youtube_video_url', 'thumbnail_url', 'gallery_urls',
            'created_at', 'updated_at',
        ];
    }

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        // Pull the category hierarchy out of the JSON category_ids column.
        $category_id = '';
        $sub_category_id = '';
        $sub_sub_category_id = '';

        $decoded_categories = json_decode($product->category_ids, true);
        if (is_array($decoded_categories)) {
            foreach ($decoded_categories as $cat) {
                if (!isset($cat['position'], $cat['id'])) {
                    continue;
                }
                if ($cat['position'] == 1) {
                    $category_id = $cat['id'];
                } elseif ($cat['position'] == 2) {
                    $sub_category_id = $cat['id'];
                } elseif ($cat['position'] == 3) {
                    $sub_sub_category_id = $cat['id'];
                }
            }
        }

        // Image paths exported as plain text URLs only (no files are touched).
        $thumbnail_url = $product->thumbnail
            ? asset('storage/app/public/product/thumbnail/' . $product->thumbnail)
            : '';

        $gallery_links = [];
        $decoded_images = json_decode($product->images, true);
        if (is_array($decoded_images)) {
            foreach ($decoded_images as $img) {
                if (!empty($img)) {
                    $gallery_links[] = asset('storage/app/public/product/' . $img);
                }
            }
        }

        // Stored in USD internally; convert back so a re-import (which applies
        // currency_to_usd) round-trips to the original value.
        $unit_price = BackEndHelper::usd_to_currency($product->unit_price);
        $purchase_price = BackEndHelper::usd_to_currency($product->purchase_price);

        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->product_code,
            $product->brand_id,
            optional($product->brand)->name ?? '',
            $category_id,
            $category_id !== '' ? ($this->category_names[$category_id] ?? '') : '',
            $sub_category_id,
            $sub_category_id !== '' ? ($this->category_names[$sub_category_id] ?? '') : '',
            $sub_sub_category_id,
            $sub_sub_category_id !== '' ? ($this->category_names[$sub_sub_category_id] ?? '') : '',
            $purchase_price,
            $unit_price,
            $unit_price,
            $product->tax,
            $product->discount,
            $product->discount_type,
            $product->current_stock,
            $product->min_qty,
            $product->unit,
            $product->refundable,
            $product->status == 1 ? 'Live' : 'Not Live',
            $product->featured_status == 1 ? 'Featured' : 'Not Featured',
            $product->details,
            $this->htmlToPlainText($product->details),
            $product->meta_title,
            $product->meta_description,
            $product->video_url,
            $thumbnail_url,
            implode(',', $gallery_links),
            $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : '',
            $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Build a human-readable plain-text version of the stored product HTML.
     * The original HTML is preserved untouched in the details_html column; this is
     * a read-only convenience column for Excel users.
     */
    private function htmlToPlainText($html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $text = (string) $html;

        // Turn line-break and block-level tags into newlines so structure survives.
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<li[^>]*>/i', "\n", $text);
        $text = preg_replace('/<(p|div|h[1-6])[^>]*>/i', "\n", $text);
        $text = preg_replace('/<\/(p|div|h[1-6]|li|ul|ol|tr|table)>/i', "\n", $text);

        // Drop every remaining tag, then decode entities (&nbsp; &plusmn; &deg; ...).
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Non-breaking spaces -> normal spaces.
        $text = str_replace("\xC2\xA0", ' ', $text);

        // Collapse whitespace, trim around line breaks, and remove blank lines.
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/[ \t]*\n[ \t]*/', "\n", $text);
        $text = preg_replace('/\n{2,}/', "\n", $text);

        return trim($text);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // id
            'B' => 35,  // name
            'C' => 35,  // slug
            'D' => 20,  // product_code
            'E' => 12,  // brand_id
            'F' => 22,  // brand_name
            'G' => 12,  // category_id
            'H' => 28,  // category_name
            'I' => 15,  // sub_category_id
            'J' => 28,  // sub_category_name
            'K' => 18,  // sub_sub_category_id
            'L' => 30,  // sub_sub_category_name
            'M' => 15,  // purchase_price
            'N' => 15,  // unit_price
            'O' => 15,  // selling_price
            'P' => 10,  // tax
            'Q' => 12,  // discount
            'R' => 15,  // discount_type
            'S' => 12,  // current_stock
            'T' => 12,  // min_qty
            'U' => 12,  // unit
            'V' => 12,  // refundable
            'W' => 15,  // status_text
            'X' => 15,  // featured_text
            'Y' => 50,  // details_html
            'Z' => 60,  // details_plain_text
            'AA' => 30, // meta_title
            'AB' => 60, // meta_description
            'AC' => 45, // youtube_video_url
            'AD' => 45, // thumbnail_url
            'AE' => 45, // gallery_urls
            'AF' => 20, // created_at
            'AG' => 20, // updated_at
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last_col = self::LAST_COLUMN;
                $highest_row = $sheet->getHighestRow();

                // Bold header + taller header row.
                $sheet->getStyle("A1:{$last_col}1")->getFont()->setBold(true);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Compact, uniform height for all data rows (set as the sheet default
                // so we don't store a dimension per row).
                $sheet->getDefaultRowDimension()->setRowHeight(22);

                // Freeze the header row and add column filters.
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$last_col}1");

                // Vertically center every cell.
                $sheet->getStyle("A1:{$last_col}{$highest_row}")
                    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Header row: centered + wrapped.
                $sheet->getStyle("A1:{$last_col}1")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setWrapText(true);

                // Horizontally center id / *_id / price / tax / discount / stock columns.
                foreach (['A', 'E', 'G', 'I', 'K', 'M', 'N', 'O', 'P', 'Q', 'S', 'T'] as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highest_row}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Wrap text only on the two details columns; everything else stays single-line.
                // Data row height stays fixed (22) so wrapped details don't make rows very tall.
                foreach (['Y', 'Z'] as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highest_row}")
                        ->getAlignment()->setWrapText(true);
                }
            },
        ];
    }
}
