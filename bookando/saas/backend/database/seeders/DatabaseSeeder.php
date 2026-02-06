<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Category;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Location;
use App\Models\Room;
use App\Models\Equipment;
use App\Models\TimeEntry;
use App\Models\Shift;
use App\Models\Absence;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Partner;
use App\Models\Voucher;
use App\Models\Bundle;
use App\Models\PricingRule;
use App\Models\JournalEntry;
use App\Models\ChartAccount;
use App\Models\SalaryDeclaration;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic Swiss salon demo data.
     *
     * Login credentials: admin@bookando.ch / bookando123
     */
    public function run(): void
    {
        // ──────────────────────────────────────────────
        // Tenant
        // ──────────────────────────────────────────────
        $tenant = Tenant::create([
            'name' => 'Beauty Salon Zürich',
            'slug' => 'beauty-salon-zurich',
            'settings' => ['currency' => 'CHF', 'timezone' => 'Europe/Zurich', 'language' => 'de'],
        ]);
        $tid = $tenant->id;

        // ──────────────────────────────────────────────
        // Admin User (login: admin@bookando.ch / bookando123)
        // Note: User model casts password as 'hashed', so pass plain text
        // ──────────────────────────────────────────────
        User::create([
            'tenant_id' => $tid,
            'first_name' => 'Admin',
            'last_name' => 'Bookando',
            'name' => 'Admin Bookando',
            'email' => 'admin@bookando.ch',
            'password' => 'bookando123',
            'role' => 'ADMIN',
        ]);

        // ──────────────────────────────────────────────
        // Categories
        // ──────────────────────────────────────────────
        $catHair = Category::create(['tenant_id' => $tid, 'name' => 'Haare', 'sort_order' => 1]);
        $catNails = Category::create(['tenant_id' => $tid, 'name' => 'Nails', 'sort_order' => 2]);
        $catCosm = Category::create(['tenant_id' => $tid, 'name' => 'Kosmetik', 'sort_order' => 3]);
        $catWell = Category::create(['tenant_id' => $tid, 'name' => 'Wellness', 'sort_order' => 4]);
        $catSpec = Category::create(['tenant_id' => $tid, 'name' => 'Specials', 'sort_order' => 5]);

        // ──────────────────────────────────────────────
        // Services (8 services matching frontend mock)
        // ──────────────────────────────────────────────
        $svc1 = Service::create(['tenant_id' => $tid, 'title' => 'Haarschnitt Damen', 'description' => 'Waschen, Schneiden, Styling', 'type' => 'SERVICE', 'category_id' => $catHair->id, 'price_minor' => 8500, 'currency' => 'CHF', 'duration' => 60, 'active' => true]);
        $svc2 = Service::create(['tenant_id' => $tid, 'title' => 'Haarschnitt Herren', 'description' => 'Klassischer Herrenschnitt', 'type' => 'SERVICE', 'category_id' => $catHair->id, 'price_minor' => 4500, 'currency' => 'CHF', 'duration' => 30, 'active' => true]);
        $svc3 = Service::create(['tenant_id' => $tid, 'title' => 'Farbe & Strähn', 'description' => 'Komplette Färbung oder Strähn', 'type' => 'SERVICE', 'category_id' => $catHair->id, 'price_minor' => 18000, 'currency' => 'CHF', 'duration' => 120, 'active' => true]);
        $svc4 = Service::create(['tenant_id' => $tid, 'title' => 'Maniküre', 'description' => 'Pflege und Lackierung', 'type' => 'SERVICE', 'category_id' => $catNails->id, 'price_minor' => 6500, 'currency' => 'CHF', 'duration' => 45, 'active' => true]);
        $svc5 = Service::create(['tenant_id' => $tid, 'title' => 'Gesichtsbehandlung', 'description' => 'Tiefenreinigung und Pflege', 'type' => 'SERVICE', 'category_id' => $catCosm->id, 'price_minor' => 15000, 'currency' => 'CHF', 'duration' => 90, 'active' => true]);
        $svc6 = Service::create(['tenant_id' => $tid, 'title' => 'Massage 60min', 'description' => 'Entspannende Ganzkörpermassage', 'type' => 'SERVICE', 'category_id' => $catWell->id, 'price_minor' => 12000, 'currency' => 'CHF', 'duration' => 60, 'active' => true]);
        $svc7 = Service::create(['tenant_id' => $tid, 'title' => 'Bart-Trimm & Rasur', 'description' => 'Professionelle Bartpflege', 'type' => 'SERVICE', 'category_id' => $catHair->id, 'price_minor' => 3500, 'currency' => 'CHF', 'duration' => 30, 'active' => true]);
        $svc8 = Service::create(['tenant_id' => $tid, 'title' => 'Braut-Styling', 'description' => 'Komplettes Styling für den grossen Tag', 'type' => 'SERVICE', 'category_id' => $catSpec->id, 'price_minor' => 35000, 'currency' => 'CHF', 'duration' => 180, 'active' => true]);

        // ──────────────────────────────────────────────
        // Customers (6 customers matching frontend mock)
        // ──────────────────────────────────────────────
        $cust1 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Maria', 'last_name' => 'Schneider', 'email' => 'maria.schneider@bluewin.ch', 'phone' => '+41 79 123 45 67', 'status' => 'ACTIVE', 'street' => 'Bahnhofstrasse 10', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'female']);
        $cust2 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Peter', 'last_name' => 'Huber', 'email' => 'peter.huber@gmail.com', 'phone' => '+41 78 234 56 78', 'status' => 'ACTIVE', 'street' => 'Langstrasse 22', 'zip' => '8004', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'male']);
        $cust3 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Sabine', 'last_name' => 'Keller', 'email' => 'sabine.keller@sunrise.ch', 'phone' => '+41 76 345 67 89', 'status' => 'ACTIVE', 'street' => 'Seestrasse 5', 'zip' => '8002', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'female']);
        $cust4 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Thomas', 'last_name' => 'Brunner', 'email' => 'thomas.brunner@swisscom.ch', 'phone' => '+41 79 456 78 90', 'status' => 'ACTIVE', 'street' => 'Limmatquai 55', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'male']);
        $cust5 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Laura', 'last_name' => 'Zimmermann', 'email' => 'laura.z@gmx.ch', 'phone' => '+41 78 567 89 01', 'status' => 'ACTIVE', 'street' => 'Rämistrasse 12', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'female']);
        $cust6 = Customer::create(['tenant_id' => $tid, 'first_name' => 'Michael', 'last_name' => 'Gerber', 'email' => 'm.gerber@bluewin.ch', 'phone' => '+41 76 678 90 12', 'status' => 'ACTIVE', 'street' => 'Hardstrasse 33', 'zip' => '8005', 'city' => 'Zürich', 'country' => 'CH', 'gender' => 'male']);

        // ──────────────────────────────────────────────
        // Employees (8 employees matching frontend employees store)
        // ──────────────────────────────────────────────
        $emp1 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Lisa', 'last_name' => 'Weber', 'email' => 'lisa@beispiel.ch', 'phone' => '+41 79 111 22 33', 'position' => 'Senior Friseurin', 'department' => 'Haarstyling', 'status' => 'ACTIVE', 'role' => 'EMPLOYEE', 'hire_date' => '2021-03-15', 'salary_minor' => 580000, 'vacation_days_total' => 25, 'vacation_days_used' => 8, 'employment_percent' => 100, 'social_security_number' => '756.1234.5678.90', 'assigned_service_ids' => [$svc1->id, $svc3->id, $svc8->id], 'street' => 'Bahnhofstrasse 12', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Spezialisiert auf Balayage und moderne Schnitte.']);
        $emp2 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Marco', 'last_name' => 'Bianchi', 'email' => 'marco@beispiel.ch', 'phone' => '+41 79 222 33 44', 'position' => 'Barbier', 'department' => 'Barbershop', 'status' => 'ACTIVE', 'role' => 'EMPLOYEE', 'hire_date' => '2022-06-01', 'salary_minor' => 520000, 'vacation_days_total' => 25, 'vacation_days_used' => 12, 'employment_percent' => 100, 'social_security_number' => '756.2345.6789.01', 'assigned_service_ids' => [$svc2->id, $svc7->id], 'street' => 'Langstrasse 45', 'zip' => '8004', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Traditionelle und moderne Barber-Techniken.']);
        $emp3 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Sarah', 'last_name' => 'Keller', 'email' => 'sarah@beispiel.ch', 'phone' => '+41 79 333 44 55', 'position' => 'Kosmetikerin', 'department' => 'Kosmetik', 'status' => 'VACATION', 'role' => 'EMPLOYEE', 'hire_date' => '2020-01-10', 'salary_minor' => 550000, 'vacation_days_total' => 25, 'vacation_days_used' => 18, 'employment_percent' => 80, 'social_security_number' => '756.3456.7890.12', 'assigned_service_ids' => [$svc4->id, $svc5->id], 'street' => 'Seestrasse 78', 'zip' => '8002', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Expertin für Gesichtsbehandlungen und Hautpflege.']);
        $emp4 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Thomas', 'last_name' => 'Brunner', 'email' => 'thomas@beispiel.ch', 'phone' => '+41 79 444 55 66', 'position' => 'Masseur', 'department' => 'Wellness', 'status' => 'PAUSE', 'role' => 'EMPLOYEE', 'hire_date' => '2023-09-01', 'salary_minor' => 480000, 'vacation_days_total' => 25, 'vacation_days_used' => 5, 'employment_percent' => 60, 'social_security_number' => '756.4567.8901.23', 'assigned_service_ids' => [$svc6->id], 'street' => 'Limmatquai 22', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Klassische Massage und Hot-Stone-Therapie.']);
        $emp5 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Anna', 'last_name' => 'Meier', 'email' => 'anna@beispiel.ch', 'phone' => '+41 79 555 66 77', 'position' => 'Salon-Managerin', 'department' => 'Management', 'status' => 'ACTIVE', 'role' => 'MANAGER', 'hire_date' => '2019-04-01', 'salary_minor' => 720000, 'vacation_days_total' => 28, 'vacation_days_used' => 10, 'employment_percent' => 100, 'social_security_number' => '756.5678.9012.34', 'assigned_service_ids' => [$svc1->id, $svc2->id, $svc3->id], 'street' => 'Rämistrasse 5', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Leitung des Teams und strategische Planung.']);
        $emp6 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Julia', 'last_name' => 'Schmidt', 'email' => 'julia@beispiel.ch', 'phone' => '+41 79 666 77 88', 'position' => 'Lernende Friseurin', 'department' => 'Haarstyling', 'status' => 'ACTIVE', 'role' => 'TRAINEE', 'hire_date' => '2025-08-01', 'salary_minor' => 120000, 'vacation_days_total' => 25, 'vacation_days_used' => 2, 'employment_percent' => 100, 'social_security_number' => '756.6789.0123.45', 'assigned_service_ids' => [$svc1->id, $svc2->id], 'street' => 'Birmensdorferstrasse 99', 'zip' => '8003', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Im 1. Lehrjahr, lernt grundlegende Schnitttechniken.']);
        $emp7 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Peter', 'last_name' => 'Huber', 'email' => 'peter@beispiel.ch', 'phone' => '+41 79 777 88 99', 'position' => 'Empfangsmitarbeiter', 'department' => 'Empfang', 'status' => 'SICK_LEAVE', 'role' => 'EMPLOYEE', 'hire_date' => '2024-02-15', 'salary_minor' => 450000, 'vacation_days_total' => 25, 'vacation_days_used' => 3, 'employment_percent' => 100, 'social_security_number' => '756.7890.1234.56', 'assigned_service_ids' => [], 'street' => 'Hardstrasse 33', 'zip' => '8005', 'city' => 'Zürich', 'country' => 'CH', 'bio' => 'Kundenempfang und Terminmanagement.']);
        $emp8 = Employee::create(['tenant_id' => $tid, 'first_name' => 'Elena', 'last_name' => 'Rossi', 'email' => 'elena@beispiel.ch', 'phone' => '+41 79 888 99 00', 'position' => 'Friseurin', 'department' => 'Haarstyling', 'status' => 'TERMINATED', 'role' => 'EMPLOYEE', 'hire_date' => '2020-06-01', 'exit_date' => '2025-09-30', 'salary_minor' => 540000, 'vacation_days_total' => 25, 'vacation_days_used' => 25, 'employment_percent' => 100, 'social_security_number' => '756.8901.2345.67', 'assigned_service_ids' => [$svc1->id, $svc3->id], 'street' => 'Zähringerstrasse 11', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'CH']);

        // ──────────────────────────────────────────────
        // Location & Rooms
        // ──────────────────────────────────────────────
        $loc1 = Location::create(['tenant_id' => $tid, 'name' => 'Hauptsalon Zürich', 'address' => 'Bahnhofstrasse 42', 'city' => 'Zürich', 'zip' => '8001', 'phone' => '+41 44 123 45 67', 'email' => 'info@beauty-salon-zurich.ch']);
        $room1 = Room::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Schneideraum 1', 'capacity' => 2, 'features' => ['Spiegel', 'Waschbecken', 'Stylingplatz']]);
        $room2 = Room::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Barber Corner', 'capacity' => 1, 'features' => ['Barber-Stuhl', 'Spiegel', 'Hot-Towel']]);
        $room3 = Room::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Kosmetik-Kabine', 'capacity' => 1, 'features' => ['Behandlungsliege', 'LED-Licht', 'Dampfgerät']]);
        $room4 = Room::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Wellness-Raum', 'capacity' => 1, 'features' => ['Massageliege', 'Dimmbar', 'Musikanlage']]);

        // ──────────────────────────────────────────────
        // Equipment
        // ──────────────────────────────────────────────
        Equipment::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Haartrockner Dyson Supersonic', 'description' => 'Premium Haartrockner', 'condition' => 'good', 'available' => true]);
        Equipment::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'Glätteisen GHD', 'description' => 'Professionelles Glätteisen', 'condition' => 'good', 'available' => true]);
        Equipment::create(['tenant_id' => $tid, 'location_id' => $loc1->id, 'name' => 'UV-Lampe Nails', 'description' => 'UV-Aushärtungslampe für Gel-Nägel', 'condition' => 'fair', 'available' => true]);

        // ──────────────────────────────────────────────
        // Appointments (10 appointments with dynamic dates)
        // ──────────────────────────────────────────────
        $today = Carbon::today()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $dayAfter = Carbon::today()->addDays(2)->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $nextWeek = Carbon::today()->addDays(5)->format('Y-m-d');

        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust1->id, 'employee_id' => $emp1->id, 'service_id' => $svc1->id, 'location_id' => $loc1->id, 'room_id' => $room1->id, 'date' => $today, 'start_time' => '09:00', 'end_time' => '10:00', 'duration' => 60, 'status' => 'CONFIRMED', 'price_minor' => 8500, 'currency' => 'CHF', 'notes' => 'Stammkundin, bevorzugt Schichtschnitt']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust2->id, 'employee_id' => $emp2->id, 'service_id' => $svc2->id, 'location_id' => $loc1->id, 'room_id' => $room2->id, 'date' => $today, 'start_time' => '10:30', 'end_time' => '11:00', 'duration' => 30, 'status' => 'CONFIRMED', 'price_minor' => 4500, 'currency' => 'CHF']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust3->id, 'employee_id' => $emp3->id, 'service_id' => $svc5->id, 'location_id' => $loc1->id, 'room_id' => $room3->id, 'date' => $today, 'start_time' => '11:00', 'end_time' => '12:30', 'duration' => 90, 'status' => 'PENDING', 'price_minor' => 15000, 'currency' => 'CHF', 'notes' => 'Empfindliche Haut, bitte hypoallergene Produkte']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust4->id, 'employee_id' => $emp2->id, 'service_id' => $svc7->id, 'location_id' => $loc1->id, 'room_id' => $room2->id, 'date' => $today, 'start_time' => '14:00', 'end_time' => '14:30', 'duration' => 30, 'status' => 'CONFIRMED', 'price_minor' => 3500, 'currency' => 'CHF']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust5->id, 'employee_id' => $emp1->id, 'service_id' => $svc3->id, 'location_id' => $loc1->id, 'room_id' => $room1->id, 'date' => $tomorrow, 'start_time' => '09:00', 'end_time' => '11:00', 'duration' => 120, 'status' => 'CONFIRMED', 'price_minor' => 18000, 'currency' => 'CHF', 'notes' => 'Blonde Strähn, Balayage-Technik']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust6->id, 'employee_id' => $emp4->id, 'service_id' => $svc6->id, 'location_id' => $loc1->id, 'room_id' => $room4->id, 'date' => $tomorrow, 'start_time' => '13:00', 'end_time' => '14:00', 'duration' => 60, 'status' => 'PENDING', 'price_minor' => 12000, 'currency' => 'CHF', 'notes' => 'Rückenprobleme, bitte vorsichtig im Lendenwirbelbereich']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust1->id, 'employee_id' => $emp3->id, 'service_id' => $svc4->id, 'location_id' => $loc1->id, 'room_id' => $room3->id, 'date' => $dayAfter, 'start_time' => '10:00', 'end_time' => '10:45', 'duration' => 45, 'status' => 'CONFIRMED', 'price_minor' => 6500, 'currency' => 'CHF']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust3->id, 'employee_id' => $emp1->id, 'service_id' => $svc8->id, 'location_id' => $loc1->id, 'room_id' => $room1->id, 'date' => $nextWeek, 'start_time' => '08:00', 'end_time' => '11:00', 'duration' => 180, 'status' => 'CONFIRMED', 'price_minor' => 35000, 'currency' => 'CHF', 'notes' => 'Hochzeit am Samstag']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust2->id, 'employee_id' => $emp2->id, 'service_id' => $svc2->id, 'location_id' => $loc1->id, 'room_id' => $room2->id, 'date' => $yesterday, 'start_time' => '15:00', 'end_time' => '15:30', 'duration' => 30, 'status' => 'COMPLETED', 'price_minor' => 4500, 'currency' => 'CHF']);
        Appointment::create(['tenant_id' => $tid, 'customer_id' => $cust4->id, 'employee_id' => $emp4->id, 'service_id' => $svc6->id, 'location_id' => $loc1->id, 'room_id' => $room4->id, 'date' => $yesterday, 'start_time' => '10:00', 'end_time' => '11:00', 'duration' => 60, 'status' => 'NO_SHOW', 'price_minor' => 12000, 'currency' => 'CHF', 'notes' => 'Nicht erschienen, keine Absage']);

        // ──────────────────────────────────────────────
        // Invoices (5 invoices with line items)
        // ──────────────────────────────────────────────
        $inv1 = Invoice::create(['tenant_id' => $tid, 'number' => 'INV-2026-00001', 'customer_id' => $cust1->id, 'status' => 'PAID', 'issue_date' => '2026-01-05', 'due_date' => '2026-02-04', 'total_minor' => 7000, 'tax_minor' => 567, 'currency' => 'CHF', 'dunning_level' => 0, 'qr_reference' => str_pad((string) rand(0, 99999999999999), 26, '0', STR_PAD_LEFT), 'payment_method' => 'QR_BILL']);
        InvoiceLineItem::create(['invoice_id' => $inv1->id, 'description' => 'Haarschnitt Herren', 'quantity' => 1, 'unit_price_minor' => 4500, 'total_minor' => 4500, 'vat_rate_percent' => 8.1]);
        InvoiceLineItem::create(['invoice_id' => $inv1->id, 'description' => 'Bartpflege', 'quantity' => 1, 'unit_price_minor' => 2500, 'total_minor' => 2500, 'vat_rate_percent' => 8.1]);

        $inv2 = Invoice::create(['tenant_id' => $tid, 'number' => 'INV-2026-00002', 'customer_id' => $cust2->id, 'status' => 'SENT', 'issue_date' => '2026-01-12', 'due_date' => '2026-02-11', 'total_minor' => 18500, 'tax_minor' => 1499, 'currency' => 'CHF', 'dunning_level' => 0, 'qr_reference' => str_pad((string) rand(0, 99999999999999), 26, '0', STR_PAD_LEFT), 'payment_method' => 'QR_BILL']);
        InvoiceLineItem::create(['invoice_id' => $inv2->id, 'description' => 'Coloration komplett', 'quantity' => 1, 'unit_price_minor' => 12000, 'total_minor' => 12000, 'vat_rate_percent' => 8.1]);
        InvoiceLineItem::create(['invoice_id' => $inv2->id, 'description' => 'Schnitt & Föhnen', 'quantity' => 1, 'unit_price_minor' => 6500, 'total_minor' => 6500, 'vat_rate_percent' => 8.1]);

        $inv3 = Invoice::create(['tenant_id' => $tid, 'number' => 'INV-2026-00003', 'customer_id' => $cust3->id, 'status' => 'OVERDUE', 'issue_date' => '2025-12-01', 'due_date' => '2025-12-31', 'total_minor' => 15000, 'tax_minor' => 1215, 'currency' => 'CHF', 'dunning_level' => 2, 'qr_reference' => str_pad((string) rand(0, 99999999999999), 26, '0', STR_PAD_LEFT), 'payment_method' => 'BANK_TRANSFER']);
        InvoiceLineItem::create(['invoice_id' => $inv3->id, 'description' => 'Gesichtsbehandlung Deluxe', 'quantity' => 1, 'unit_price_minor' => 15000, 'total_minor' => 15000, 'vat_rate_percent' => 8.1]);

        $inv4 = Invoice::create(['tenant_id' => $tid, 'number' => 'INV-2026-00004', 'customer_id' => $cust4->id, 'status' => 'DRAFT', 'issue_date' => '2026-02-01', 'due_date' => '2026-03-03', 'total_minor' => 21000, 'tax_minor' => 1701, 'currency' => 'CHF', 'dunning_level' => 0, 'qr_reference' => str_pad((string) rand(0, 99999999999999), 26, '0', STR_PAD_LEFT), 'payment_method' => 'QR_BILL']);
        InvoiceLineItem::create(['invoice_id' => $inv4->id, 'description' => 'Massage 60min', 'quantity' => 2, 'unit_price_minor' => 9000, 'total_minor' => 18000, 'vat_rate_percent' => 8.1]);
        InvoiceLineItem::create(['invoice_id' => $inv4->id, 'description' => 'Aromaöl-Upgrade', 'quantity' => 2, 'unit_price_minor' => 1500, 'total_minor' => 3000, 'vat_rate_percent' => 8.1]);

        $inv5 = Invoice::create(['tenant_id' => $tid, 'number' => 'INV-2026-00005', 'customer_id' => $cust5->id, 'status' => 'PAID', 'issue_date' => '2026-01-20', 'due_date' => '2026-02-19', 'total_minor' => 12000, 'tax_minor' => 972, 'currency' => 'CHF', 'dunning_level' => 0, 'qr_reference' => str_pad((string) rand(0, 99999999999999), 26, '0', STR_PAD_LEFT), 'payment_method' => 'TWINT']);
        InvoiceLineItem::create(['invoice_id' => $inv5->id, 'description' => 'Maniküre', 'quantity' => 1, 'unit_price_minor' => 5500, 'total_minor' => 5500, 'vat_rate_percent' => 8.1]);
        InvoiceLineItem::create(['invoice_id' => $inv5->id, 'description' => 'Pediküre', 'quantity' => 1, 'unit_price_minor' => 6500, 'total_minor' => 6500, 'vat_rate_percent' => 8.1]);

        // ──────────────────────────────────────────────
        // Journal Entries (double-entry bookkeeping)
        // ──────────────────────────────────────────────
        JournalEntry::create(['tenant_id' => $tid, 'date' => '2026-01-05', 'account' => '1100', 'contra_account' => '3400', 'description' => 'Rechnung INV-2026-00001 – Maria Schneider', 'debit_minor' => 7000, 'credit_minor' => 0, 'reference' => 'INV-2026-00001']);
        JournalEntry::create(['tenant_id' => $tid, 'date' => '2026-01-12', 'account' => '1100', 'contra_account' => '3400', 'description' => 'Rechnung INV-2026-00002 – Peter Huber', 'debit_minor' => 18500, 'credit_minor' => 0, 'reference' => 'INV-2026-00002']);
        JournalEntry::create(['tenant_id' => $tid, 'date' => '2026-01-20', 'account' => '1020', 'contra_account' => '1100', 'description' => 'Zahlung Laura Zimmermann (TWINT)', 'debit_minor' => 12000, 'credit_minor' => 0, 'reference' => 'INV-2026-00005']);
        JournalEntry::create(['tenant_id' => $tid, 'date' => '2026-01-31', 'account' => '5000', 'contra_account' => '1020', 'description' => 'Löhne Januar 2026', 'debit_minor' => 0, 'credit_minor' => 1850000, 'reference' => 'SAL-2026-01']);
        JournalEntry::create(['tenant_id' => $tid, 'date' => '2026-01-31', 'account' => '2200', 'contra_account' => '1020', 'description' => 'MwSt-Abrechnung Q4/2025', 'debit_minor' => 0, 'credit_minor' => 485000, 'reference' => 'VAT-2025-Q4']);

        // ──────────────────────────────────────────────
        // Chart of Accounts (Swiss KMU Kontenrahmen)
        // ──────────────────────────────────────────────
        foreach ([
            ['1020', 'Bankguthaben (PostFinance)', 'ASSET', 8745000],
            ['1100', 'Forderungen aus L/L', 'ASSET', 5550000],
            ['1170', 'Vorsteuer MwSt', 'ASSET', 124000],
            ['1200', 'Warenvorräte', 'ASSET', 320000],
            ['2000', 'Verbindlichkeiten aus L/L', 'LIABILITY', 185000],
            ['2200', 'Geschuldete MwSt', 'LIABILITY', 485000],
            ['2270', 'Sozialversicherungen', 'LIABILITY', 310000],
            ['2800', 'Eigenkapital', 'LIABILITY', 5000000],
            ['3400', 'Dienstleistungsertrag', 'REVENUE', 12450000],
            ['3800', 'Rabatte / Skonti', 'REVENUE', -85000],
            ['4400', 'Aufwand Material', 'EXPENSE', 890000],
            ['5000', 'Lohnaufwand', 'EXPENSE', 5550000],
            ['5700', 'Sozialversicherungsaufwand', 'EXPENSE', 720000],
            ['6000', 'Mietaufwand', 'EXPENSE', 3600000],
            ['6500', 'Verwaltungsaufwand', 'EXPENSE', 245000],
        ] as [$num, $name, $type, $bal]) {
            ChartAccount::create(['tenant_id' => $tid, 'number' => $num, 'name' => $name, 'type' => $type, 'balance_minor' => $bal]);
        }

        // ──────────────────────────────────────────────
        // Salary Declarations (Swiss social security deductions)
        // ──────────────────────────────────────────────
        SalaryDeclaration::create(['tenant_id' => $tid, 'employee_id' => $emp1->id, 'year' => 2026, 'month' => 1, 'gross_minor' => 650000, 'ahv_minor' => 34450, 'alv_minor' => 7150, 'bvg_minor' => 32500, 'nbu_minor' => 4550, 'tax_minor' => 45500, 'net_minor' => 525850, 'status' => 'CONFIRMED']);
        SalaryDeclaration::create(['tenant_id' => $tid, 'employee_id' => $emp2->id, 'year' => 2026, 'month' => 1, 'gross_minor' => 580000, 'ahv_minor' => 30740, 'alv_minor' => 6380, 'bvg_minor' => 29000, 'nbu_minor' => 4060, 'tax_minor' => 38200, 'net_minor' => 471620, 'status' => 'SUBMITTED']);
        SalaryDeclaration::create(['tenant_id' => $tid, 'employee_id' => $emp3->id, 'year' => 2026, 'month' => 1, 'gross_minor' => 620000, 'ahv_minor' => 32860, 'alv_minor' => 6820, 'bvg_minor' => 31000, 'nbu_minor' => 4340, 'tax_minor' => 42500, 'net_minor' => 502480, 'status' => 'DRAFT']);

        // ──────────────────────────────────────────────
        // Vouchers
        // ──────────────────────────────────────────────
        Voucher::create(['tenant_id' => $tid, 'code' => 'WELCOME10', 'description' => '10% Willkommensrabatt', 'discount_percent' => 10, 'usage_count' => 15, 'max_uses' => 100, 'expires_at' => '2026-12-31', 'active' => true]);
        Voucher::create(['tenant_id' => $tid, 'code' => 'SUMMER25', 'description' => 'CHF 25 Sommerrabatt', 'discount_minor' => 2500, 'usage_count' => 8, 'max_uses' => 50, 'expires_at' => '2026-09-30', 'active' => true]);

        // ──────────────────────────────────────────────
        // Bundles (service packages)
        // ──────────────────────────────────────────────
        Bundle::create(['tenant_id' => $tid, 'name' => 'Beauty-Paket Komplett', 'description' => 'Haarschnitt + Maniküre + Gesichtsbehandlung', 'price_minor' => 25000, 'savings_minor' => 5000, 'service_ids' => [$svc1->id, $svc4->id, $svc5->id]]);
        Bundle::create(['tenant_id' => $tid, 'name' => 'Herren-Paket', 'description' => 'Haarschnitt + Bartpflege', 'price_minor' => 7000, 'savings_minor' => 1000, 'service_ids' => [$svc2->id, $svc7->id]]);

        // ──────────────────────────────────────────────
        // Pricing Rules
        // ──────────────────────────────────────────────
        PricingRule::create(['tenant_id' => $tid, 'name' => 'Frühbucher-Rabatt', 'type' => 'earlyBird', 'discount_percent' => 10, 'conditions' => ['days_ahead' => 14], 'active' => true]);
        PricingRule::create(['tenant_id' => $tid, 'name' => 'Last-Minute', 'type' => 'lastMinute', 'discount_percent' => 15, 'conditions' => ['hours_before' => 4], 'active' => true]);

        // ──────────────────────────────────────────────
        // Courses & Lessons
        // ──────────────────────────────────────────────
        $course1 = Course::create(['tenant_id' => $tid, 'title' => 'Balayage Masterclass', 'description' => 'Professionelle Balayage-Technik für Fortgeschrittene', 'type' => 'inPerson', 'difficulty' => 'advanced', 'visibility' => 'public', 'duration_hours' => 8, 'price_minor' => 45000, 'currency' => 'CHF', 'certificate' => true, 'max_participants' => 12]);
        Lesson::create(['course_id' => $course1->id, 'title' => 'Einführung & Theorie', 'content' => 'Grundlagen der Balayage-Technik', 'sort_order' => 1, 'duration_minutes' => 60]);
        Lesson::create(['course_id' => $course1->id, 'title' => 'Praxis am Übungskopf', 'content' => 'Üben der Technik', 'sort_order' => 2, 'duration_minutes' => 120]);
        Lesson::create(['course_id' => $course1->id, 'title' => 'Live-Model', 'content' => 'Anwendung an echtem Model', 'sort_order' => 3, 'duration_minutes' => 180]);

        $course2 = Course::create(['tenant_id' => $tid, 'title' => 'Hautpflege Basics', 'description' => 'Grundlagen der professionellen Hautanalyse und -pflege', 'type' => 'online', 'difficulty' => 'beginner', 'visibility' => 'public', 'duration_hours' => 4, 'price_minor' => 0, 'currency' => 'CHF', 'certificate' => false, 'max_participants' => null]);
        Lesson::create(['course_id' => $course2->id, 'title' => 'Hauttypen erkennen', 'content' => 'Die verschiedenen Hauttypen und ihre Merkmale', 'sort_order' => 1, 'duration_minutes' => 45]);
        Lesson::create(['course_id' => $course2->id, 'title' => 'Reinigung & Peeling', 'content' => 'Richtige Reinigungstechniken', 'sort_order' => 2, 'duration_minutes' => 60]);

        // ──────────────────────────────────────────────
        // Partners
        // ──────────────────────────────────────────────
        Partner::create(['tenant_id' => $tid, 'name' => 'Kérastase Schweiz', 'type' => 'supplier', 'email' => 'info@kerastase.ch', 'phone' => '+41 44 111 22 33', 'revenue_share_percent' => 0, 'status' => 'ACTIVE', 'data_processing_agreement' => true]);
        Partner::create(['tenant_id' => $tid, 'name' => 'Salon Partner GmbH', 'type' => 'referral', 'email' => 'partner@salon-partner.ch', 'phone' => '+41 44 222 33 44', 'revenue_share_percent' => 5, 'status' => 'ACTIVE', 'data_processing_agreement' => true]);

        // ──────────────────────────────────────────────
        // Time Entries (today for active employees)
        // ──────────────────────────────────────────────
        TimeEntry::create(['tenant_id' => $tid, 'employee_id' => $emp1->id, 'date' => $today, 'clock_in' => '08:00', 'clock_out' => '17:00', 'break_minutes' => 60]);
        TimeEntry::create(['tenant_id' => $tid, 'employee_id' => $emp2->id, 'date' => $today, 'clock_in' => '09:00', 'clock_out' => '18:00', 'break_minutes' => 60]);
        TimeEntry::create(['tenant_id' => $tid, 'employee_id' => $emp5->id, 'date' => $today, 'clock_in' => '07:30', 'clock_out' => '16:30', 'break_minutes' => 45]);

        // ──────────────────────────────────────────────
        // Shifts
        // ──────────────────────────────────────────────
        Shift::create(['tenant_id' => $tid, 'employee_id' => $emp1->id, 'date' => $today, 'type' => 'early', 'start_time' => '08:00', 'end_time' => '17:00']);
        Shift::create(['tenant_id' => $tid, 'employee_id' => $emp2->id, 'date' => $today, 'type' => 'late', 'start_time' => '09:00', 'end_time' => '18:00']);
        Shift::create(['tenant_id' => $tid, 'employee_id' => $emp6->id, 'date' => $today, 'type' => 'early', 'start_time' => '08:00', 'end_time' => '17:00']);

        // ──────────────────────────────────────────────
        // Absences
        // ──────────────────────────────────────────────
        Absence::create(['tenant_id' => $tid, 'employee_id' => $emp3->id, 'type' => 'vacation', 'start_date' => Carbon::today()->subDays(3)->format('Y-m-d'), 'end_date' => Carbon::today()->addDays(4)->format('Y-m-d'), 'status' => 'APPROVED', 'notes' => 'Winterferien']);
        Absence::create(['tenant_id' => $tid, 'employee_id' => $emp7->id, 'type' => 'sick', 'start_date' => Carbon::today()->subDays(1)->format('Y-m-d'), 'end_date' => Carbon::today()->addDays(2)->format('Y-m-d'), 'status' => 'APPROVED', 'notes' => 'Ärztliches Zeugnis vorhanden']);

        // ──────────────────────────────────────────────
        // Settings
        // ──────────────────────────────────────────────
        Setting::create(['tenant_id' => $tid, 'key' => 'language', 'value' => 'de']);
        Setting::create(['tenant_id' => $tid, 'key' => 'timezone', 'value' => 'Europe/Zurich']);
        Setting::create(['tenant_id' => $tid, 'key' => 'currency', 'value' => 'CHF']);
        Setting::create(['tenant_id' => $tid, 'key' => 'date_format', 'value' => 'dd.MM.yyyy']);
        Setting::create(['tenant_id' => $tid, 'key' => 'company_name', 'value' => 'Beauty Salon Zürich']);
        Setting::create(['tenant_id' => $tid, 'key' => 'company_email', 'value' => 'info@beauty-salon-zurich.ch']);
        Setting::create(['tenant_id' => $tid, 'key' => 'company_phone', 'value' => '+41 44 123 45 67']);
        Setting::create(['tenant_id' => $tid, 'key' => 'company_address', 'value' => 'Bahnhofstrasse 42, 8001 Zürich']);
        Setting::create(['tenant_id' => $tid, 'key' => 'iban', 'value' => 'CH93 0076 2011 6238 5295 7']);
        Setting::create(['tenant_id' => $tid, 'key' => 'qr_iban', 'value' => 'CH44 3199 9123 0008 8901 2']);
        Setting::create(['tenant_id' => $tid, 'key' => 'vat_id', 'value' => 'CHE-123.456.789 MWST']);
    }
}
