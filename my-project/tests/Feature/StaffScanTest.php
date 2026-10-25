<?php

use App\Models\Bicycle;
use App\Models\Borrower;
use App\Models\Rental;
use App\Models\Staff;
use App\Support\RentalPricing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Schema::dropIfExists('payment');
    Schema::dropIfExists('rental');
    Schema::dropIfExists('borrower');
    Schema::dropIfExists('bicycle');
    Schema::dropIfExists('staff');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->unsignedInteger('admin_id');
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->string('phone')->nullable();
        $table->string('profile_picture')->nullable();
        $table->text('permissions')->nullable();
        $table->string('role');
        $table->string('status');
    });

    Schema::create('bicycle', function (Blueprint $table) {
        $table->increments('bike_id');
        $table->string('qr_code', 100)->unique();
        $table->string('model', 100);
        $table->string('make', 100);
        $table->string('bike_type', 50)->default('Standard');
        $table->string('status', 30)->default('available');
        $table->string('condition', 30)->default('good');
        $table->dateTime('created_at')->useCurrent();
    });

    Schema::create('borrower', function (Blueprint $table) {
        $table->increments('borrower_id');
        $table->string('full_name', 150);
        $table->string('contact_no', 30)->nullable();
        $table->string('id_photo_id', 255)->nullable();
        $table->dateTime('registered_at')->useCurrent();
    });

    Schema::create('rental', function (Blueprint $table) {
        $table->increments('rental_id');
        $table->unsignedInteger('borrower_id');
        $table->unsignedInteger('bike_id');
        $table->unsignedInteger('staff_id');
        $table->dateTime('start_time');
        $table->dateTime('end_time')->nullable();
        $table->decimal('rental_duration_hrs', 8, 2)->nullable();
        $table->decimal('base_fee', 10, 2)->default(0);
        $table->decimal('overtime_fee', 10, 2)->default(0);
        $table->decimal('total_fee', 10, 2)->default(0);
        $table->string('status', 30)->default('active');
    });

    Schema::create('payment', function (Blueprint $table) {
        $table->increments('payment_id');
        $table->unsignedInteger('rental_id');
        $table->decimal('amount_paid', 10, 2);
        $table->string('payment_status', 30)->default('pending');
        $table->dateTime('paid_at')->nullable();
        $table->string('digital_receipt_ref', 255)->nullable();
    });

    $this->staff = Staff::create([
        'admin_id' => 1,
        'username' => 'scan-staff',
        'password_hash' => Hash::make('staff-password'),
        'full_name' => 'Scan Staff',
        'first_name' => 'Scan',
        'last_name' => 'Staff',
        'email' => 'scan-staff@example.test',
        'role' => 'Staff',
        'status' => 'active',
        'permissions' => [],
    ]);
});

function makeScanBike(string $code, string $status, string $condition, string $type = 'Mountain Bike'): Bicycle
{
    return Bicycle::create([
        'qr_code' => $code,
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => $type,
        'status' => $status,
        'condition' => $condition,
    ]);
}

function scanAsStaff($test, string $code)
{
    return $test->withSession(['staff_id' => $test->staff->staff_id])
        ->getJson('/staff/scan/' . $code);
}

test('scan requires a staff session', function () {
    makeScanBike('RB-001', 'available', 'good');

    // staff.auth mo-redirect sa staff login kung walay session
    $this->getJson('/staff/scan/RB-001')->assertRedirect(route('staff.login'));
});

test('available good bike returns rent as the next action', function () {
    makeScanBike('RB-004', 'available', 'good');

    scanAsStaff($this, 'RB-004')
        ->assertOk()
        ->assertJsonPath('next_action', 'rent')
        ->assertJsonPath('bike.qr_code', 'RB-004');
});

test('rented bike returns return as the next action', function () {
    makeScanBike('RB-005', 'rented', 'good');

    scanAsStaff($this, 'RB-005')
        ->assertOk()
        ->assertJsonPath('next_action', 'return');
});

test('unknown or malformed code returns 404 Bike not found', function () {
    scanAsStaff($this, 'RB-999')
        ->assertStatus(404)
        ->assertJsonPath('message', 'Bike not found');

    scanAsStaff($this, 'bad%20code!')
        ->assertStatus(404)
        ->assertJsonPath('message', 'Bike not found');
});

test('bikes under repair, missing, or in maintenance return 422', function (string $status, string $condition) {
    makeScanBike('RB-006', $status, $condition);

    scanAsStaff($this, 'RB-006')
        ->assertStatus(422)
        ->assertJsonPath('message', 'This bike cannot be rented');
})->with([
    'condition repair' => ['repair', 'repair'],
    'condition missing' => ['available', 'missing'],
    'status maintenance' => ['maintenance', 'good'],
    'status repair' => ['repair', 'good'],
]);

test('scan lookup never changes the bike', function () {
    makeScanBike('RB-007', 'available', 'good');

    scanAsStaff($this, 'RB-007')->assertOk();

    $this->assertDatabaseHas('bicycle', ['qr_code' => 'RB-007', 'status' => 'available']);
});

test('available e-scooter returns the 15/30/60 minute packages', function () {
    makeScanBike('ES-001', 'available', 'good', 'E-Scooter');

    $response = scanAsStaff($this, 'ES-001')->assertOk();

    expect(collect($response->json('pricing.options'))->pluck('minutes')->all())->toEqual([15, 30, 60]);
    expect(collect($response->json('pricing.options'))->pluck('price')->map(fn ($p) => (int) $p)->all())->toEqual([50, 100, 200]);
    expect($response->json('server_time'))->toMatch('/^\d{2}:\d{2}$/');
});

test('available mountain bike returns hourly options', function () {
    makeScanBike('RB-010', 'available', 'good', 'Mountain Bike');

    $response = scanAsStaff($this, 'RB-010')->assertOk();

    expect(collect($response->json('pricing.options'))->pluck('minutes')->all())->toEqual([60, 120, 180]);
    expect(collect($response->json('pricing.options'))->pluck('price')->map(fn ($p) => (int) $p)->all())->toEqual([50, 100, 150]);
});

test('rented bike with an active rental returns the session details', function () {
    $bike = makeScanBike('ES-002', 'rented', 'good', 'E-Scooter');
    $borrower = Borrower::create(['full_name' => 'Juan Dela Cruz', 'contact_no' => '09171234567']);
    Rental::create([
        'borrower_id' => $borrower->borrower_id,
        'bike_id' => $bike->bike_id,
        'staff_id' => $this->staff->staff_id,
        'start_time' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
        'rental_duration_hrs' => 0.50,
        'base_fee' => 100,
        'total_fee' => 100,
        'status' => 'active',
    ]);

    $response = scanAsStaff($this, 'ES-002')
        ->assertOk()
        ->assertJsonPath('next_action', 'return')
        ->assertJsonPath('rental.borrower_name', 'Juan Dela Cruz')
        ->assertJsonPath('rental.duration_minutes', 30);

    expect($response->json('rental.seconds_remaining'))->toBeGreaterThan(1100)->toBeLessThanOrEqual(1200);
    expect(collect($response->json('rental.extend_options'))->pluck('minutes')->all())->toEqual([15, 30, 60]);
});

test('overdue fee uses the selected package rate and rounded-up overtime hours', function () {
    $bike = makeScanBike('ES-009', 'rented', 'good', 'E-Scooter');
    $borrower = Borrower::create(['full_name' => 'Juan Dela Cruz', 'contact_no' => '09171234567']);
    Rental::create([
        'borrower_id' => $borrower->borrower_id,
        'bike_id' => $bike->bike_id,
        'staff_id' => $this->staff->staff_id,
        'start_time' => now()->subMinutes(45)->format('Y-m-d H:i:s'),
        'rental_duration_hrs' => 0.50,
        'base_fee' => 100,
        'total_fee' => 100,
        'status' => 'active',
    ]);

    $response = scanAsStaff($this, 'ES-009')
        ->assertOk()
        ->assertJsonPath('rental.overdue', true)
        ->assertJsonPath('rental.overtime_rate_per_hour', 200)
        ->assertJsonPath('rental.estimated_overtime_fee', 200);

    expect($response->json('rental.overtime_minutes'))->toBeGreaterThanOrEqual(14);
});

test('rented bike scan includes a protected borrower ID preview URL', function () {
    Storage::fake('local');
    Storage::disk('local')->put('borrower-ids/borrower-id.pdf', '%PDF-1.4 test ID');

    $bike = makeScanBike('ES-003', 'rented', 'good', 'E-Scooter');
    $borrower = Borrower::create([
        'full_name' => 'Maria Santos',
        'contact_no' => '09171234567',
        'id_photo_id' => 'borrower-ids/borrower-id.pdf',
    ]);
    $rental = Rental::create([
        'borrower_id' => $borrower->borrower_id,
        'bike_id' => $bike->bike_id,
        'staff_id' => $this->staff->staff_id,
        'start_time' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
        'rental_duration_hrs' => 0.50,
        'base_fee' => 100,
        'total_fee' => 100,
        'status' => 'active',
    ]);

    $response = scanAsStaff($this, 'ES-003')
        ->assertOk()
        ->assertJsonPath('rental.borrower_name', 'Maria Santos')
        ->assertJsonPath('rental.borrower_id_url', route('staff.rentals.borrower-id', $rental));

    $this->withSession(['staff_id' => $this->staff->staff_id])
        ->get($response->json('rental.borrower_id_url'))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=borrower-id.pdf')
        ->assertSee('%PDF-1.4 test ID', false);
});

test('rented bike without a rental record returns rental null', function () {
    makeScanBike('RB-011', 'rented', 'good');

    scanAsStaff($this, 'RB-011')
        ->assertOk()
        ->assertJsonPath('next_action', 'return')
        ->assertJsonPath('rental', null);
});

test('rental pricing per bike type', function () {
    expect(RentalPricing::priceFor('E-Scooter', 15))->toEqual(50);
    expect(RentalPricing::priceFor('E-Scooter', 30))->toEqual(100);
    expect(RentalPricing::priceFor('E-Scooter', 60))->toEqual(200);
    expect(RentalPricing::priceFor('E-Scooter', 45))->toBeNull();

    expect(RentalPricing::priceFor('City Bike', 60))->toEqual(30);
    expect(RentalPricing::priceFor("Lady's/Men's Bike", 120))->toEqual(60);
    expect(RentalPricing::priceFor('Mountain Bike', 60))->toEqual(50);
    expect(RentalPricing::priceFor('Sidecar Bike', 60))->toEqual(100);
    expect(RentalPricing::priceFor('Tricycle', 60))->toEqual(100);
    expect(RentalPricing::priceFor('standard', 60))->toEqual(30); // unknown type = default
    expect(RentalPricing::priceFor('City Bike', 90))->toBeNull(); // dili offered nga duration

    // extension: 30 min sa city bike = half sa hourly rate
    expect(RentalPricing::priceFor('City Bike', 30, true))->toEqual(15);
    expect(RentalPricing::priceFor('E-Scooter', 15, true))->toEqual(50);
});

test('overtime penalty applies the selected package rate per started hour', function () {
    expect(RentalPricing::overtimePenalty(0, 200))->toEqual(0);
    expect(RentalPricing::overtimePenalty(16, 200))->toEqual(200);
    expect(RentalPricing::overtimePenalty(60, 200))->toEqual(200);
    expect(RentalPricing::overtimePenalty(125, 200))->toEqual(600);
});