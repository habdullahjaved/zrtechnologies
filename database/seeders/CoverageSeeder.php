<?php

namespace Database\Seeders;

use App\Models\AvailabilityWindow;
use App\Models\BlackoutDate;
use App\Models\ServiceArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Draft service areas (brief §4), the weekly collection timetable and UK
 * (England & Wales) bank holidays as blackout dates.
 *
 * All draft data for the owner to confirm. Safe to run repeatedly and in
 * production: records are only created when missing.
 */
class CoverageSeeder extends Seeder
{
    /**
     * Core towns are active; Preston, Burnley and Chorley are optional and start switched off.
     *
     * @var list<array{name: string, town: string, districts: list<string>, lat: float, lng: float, fee: int, active: bool, summary: string}>
     */
    private const array AREAS = [
        ['name' => 'Blackburn', 'town' => 'Blackburn', 'districts' => ['BB1', 'BB2'], 'lat' => 53.748600, 'lng' => -2.487700, 'fee' => 0, 'active' => true,
            'summary' => 'Blackburn town centre and the surrounding areas, including Wilpshire, Rishton, Feniscowles and Mellor Brook.'],
        ['name' => 'Darwen', 'town' => 'Darwen', 'districts' => ['BB3'], 'lat' => 53.698000, 'lng' => -2.464500, 'fee' => 0, 'active' => true,
            'summary' => 'Darwen and Hoddlesden.'],
        ['name' => 'Accrington & Oswaldtwistle', 'town' => 'Accrington', 'districts' => ['BB5'], 'lat' => 53.753400, 'lng' => -2.364000, 'fee' => 500, 'active' => true,
            'summary' => 'Accrington, Oswaldtwistle, Church, Clayton-le-Moors and Huncoat.'],
        ['name' => 'Great Harwood', 'town' => 'Great Harwood', 'districts' => ['BB6'], 'lat' => 53.785600, 'lng' => -2.408500, 'fee' => 500, 'active' => true,
            'summary' => 'Great Harwood and Langho.'],
        ['name' => 'Clitheroe', 'town' => 'Clitheroe', 'districts' => ['BB7'], 'lat' => 53.871100, 'lng' => -2.393400, 'fee' => 1000, 'active' => true,
            'summary' => 'Clitheroe and nearby Ribble Valley villages, including Whalley and Barrow.'],
        ['name' => 'Preston', 'town' => 'Preston', 'districts' => ['PR1', 'PR2'], 'lat' => 53.763200, 'lng' => -2.703100, 'fee' => 1500, 'active' => false,
            'summary' => 'Preston city centre, Ashton-on-Ribble, Fulwood and Ribbleton.'],
        ['name' => 'Burnley', 'town' => 'Burnley', 'districts' => ['BB10', 'BB11', 'BB12'], 'lat' => 53.789300, 'lng' => -2.240500, 'fee' => 1500, 'active' => false,
            'summary' => 'Burnley, Padiham and Briercliffe.'],
        ['name' => 'Chorley', 'town' => 'Chorley', 'districts' => ['PR6', 'PR7'], 'lat' => 53.653100, 'lng' => -2.632700, 'fee' => 1500, 'active' => false,
            'summary' => 'Chorley, Adlington and Whittle-le-Woods.'],
    ];

    /**
     * Weekly collection windows: [weekdays, label, start, end, capacity].
     *
     * @var list<array{0: list<int>, 1: string, 2: string, 3: string, 4: int}>
     */
    private const array WINDOWS = [
        [[1, 2, 3, 4, 5], 'Morning', '09:00', '12:00', 4],
        [[1, 2, 3, 4, 5], 'Afternoon', '12:00', '15:00', 4],
        [[1, 2, 3, 4, 5], 'Late afternoon', '15:00', '18:00', 3],
        [[6], 'Morning', '10:00', '13:00', 3],
        [[6], 'Afternoon', '13:00', '16:00', 3],
    ];

    /**
     * England & Wales bank holidays (gov.uk), including substitute days.
     *
     * @var array<string, string>
     */
    private const array BANK_HOLIDAYS = [
        '2026-12-25' => 'Christmas Day',
        '2026-12-28' => 'Boxing Day (substitute day)',
        '2027-01-01' => 'New Year’s Day',
        '2027-03-26' => 'Good Friday',
        '2027-03-29' => 'Easter Monday',
        '2027-05-03' => 'Early May bank holiday',
        '2027-05-31' => 'Spring bank holiday',
        '2027-08-30' => 'Summer bank holiday',
        '2027-12-27' => 'Christmas Day (substitute day)',
        '2027-12-28' => 'Boxing Day (substitute day)',
    ];

    public function run(): void
    {
        foreach (self::AREAS as $index => $area) {
            ServiceArea::query()->firstOrCreate(['slug' => Str::slug($area['name'])], [
                'name' => $area['name'],
                'town' => $area['town'],
                'county' => 'Lancashire',
                'postcode_districts' => $area['districts'],
                'centre_lat' => $area['lat'],
                'centre_lng' => $area['lng'],
                'collection_fee_pence' => $area['fee'],
                'typical_collection_times' => 'Monday to Saturday, in the time windows shown when you book.',
                'summary' => $area['summary'],
                'is_active' => $area['active'],
                'is_draft' => true,
                'sort' => ($index + 1) * 10,
            ]);
        }

        if (! AvailabilityWindow::query()->exists()) {
            foreach (self::WINDOWS as [$weekdays, $label, $startsAt, $endsAt, $capacity]) {
                foreach ($weekdays as $weekday) {
                    AvailabilityWindow::query()->create([
                        'weekday' => $weekday,
                        'label' => $label,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'capacity' => $capacity,
                    ]);
                }
            }
        }

        foreach (self::BANK_HOLIDAYS as $date => $reason) {
            BlackoutDate::query()->firstOrCreate(['date' => $date], ['reason' => $reason]);
        }
    }
}
