<?php

use App\Models\Admin;
use App\Models\Bicycle;
use App\Support\RentalPricing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    foreach (['payment', 'rental', 'borrower', 'activity_log', 'bicycle', 'rental_price_settings', 'admin'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username')->unique();
        $table->string('full_name');
        $table->string('email')->unique();
        $table->string('email_hash')->nullable();
        $table->string('password_hash');
    });

    Schema::create('bicycle', function (Blueprint $table) {
        $table->increments('bike_id');
        $table->string('qr_code', 100)->unique();
        $table->string('model', 100);
        $table->string('make', 100);
        $table->string('bike_type', 50);
        $table->string('status', 30)->default('available');
        $table->string('condition', 30)->default('good');
    });

    Schema::create('rental_price_settings', function (Blueprint $table) {
        $table->id();
        $table->string('bike_type', 50);
        $table->unsignedSmallInteger('duration_minutes');
        $table->decimal('price', 10, 2);
        $table->timestamps();
        $table->unique(['bike_type', 'duration_minutes']);
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
        $table->unsignedInteger('staff_id')->nullable();
        $table->unsignedInteger('admin_id')->nullable();
        $table->dateTime('start_time');
        $table->dateTime('end_time')->nullable();
        $table->decimal('rental_duration_hrs', 8, 2);
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

    Schema::create('activity_log', function (Blueprint $table) {
        $table->increments('log_id');
        $table->unsignedInteger('user_id');
        $table->string('user_type', 30);
        $table->string('action', 100);
        $table->dateTime('timestamp')->useCurrent();
        $table->text('details')->nullable();
    });

    DB::table('rental_price_settings')->insert([
        ['bike_type' => 'Mountain Bike', 'duration_minutes' => 60, 'price' => 50],
        ['bike_type' => 'Mountain Bike', 'duration_minutes' => 120, 'price' => 100],
        ['bike_type' => 'Mountain Bike', 'duration_minutes' => 180, 'price' => 150],
    ]);
});

test('admin can rent an available bike directly and is recorded as the operator', function () {
    Storage::fake('local');
    $admin = Admin::create([
        'username' => 'admin-renter',
        'full_name' => 'Rental Admin',
        'email' => 'rental-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-101',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $this->actingAs($admin, 'web')
        ->postJson(route('admin.bikes.rent', $bike), [
            'borrower_name' => 'Maria Santos',
            'contact_no' => '09171234567',
            'borrower_id' => UploadedFile::fake()->image('valid-id.jpg'),
            'minutes' => 60,
            'borrow_time' => RentalPricing::dbNow()->format('H:i'),
        ])
        ->assertCreated()
        ->assertJsonPath('rental.duration_minutes', 60)
        ->assertJsonPath('rental.base_fee', 50);

    $this->assertDatabaseHas('rental', [
        'bike_id' => $bike->bike_id,
        'staff_id' => null,
        'admin_id' => $admin->admin_id,
        'status' => 'active',
    ]);
    $rentalId = DB::table('rental')->value('rental_id');
    $this->assertDatabaseHas('payment', [
        'rental_id' => $rentalId,
        'amount_paid' => 50,
        'payment_status' => 'pending',
    ]);
    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'status' => 'rented',
    ]);

    $idPath = DB::table('borrower')->value('id_photo_id');
    Storage::disk('local')->assertExists($idPath);
    $this->assertDatabaseHas('activity_log', [
        'user_id' => $admin->admin_id,
        'user_type' => 'admin',
        'action' => 'OPEN_RENTAL',
    ]);
});

test('admin cannot rent a bike that is under maintenance', function () {
    $admin = Admin::create([
        'username' => 'admin-renter',
        'full_name' => 'Rental Admin',
        'email' => 'rental-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-102',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'repair',
        'condition' => 'repair',
    ]);

    $this->actingAs($admin, 'web')
        ->postJson(route('admin.bikes.rent', $bike), [
            'borrower_name' => 'Maria Santos',
            'contact_no' => '09171234567',
            'borrower_id' => UploadedFile::fake()->image('valid-id.jpg'),
            'minutes' => 60,
            'borrow_time' => RentalPricing::dbNow()->format('H:i'),
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This bike cannot be rented because it is not in good condition.');

    $this->assertDatabaseCount('rental', 0);
    $this->assertDatabaseCount('borrower', 0);
});

test('admin cannot mark a bike rented without opening a rental', function () {
    $admin = Admin::create([
        'username' => 'admin-renter',
        'full_name' => 'Rental Admin',
        'email' => 'rental-admin@example.test',
        'password_hash' => Hash::make('secret-password'),
    ]);
    $bike = Bicycle::create([
        'qr_code' => 'MB-103',
        'model' => 'Trail 200',
        'make' => 'Trek',
        'bike_type' => 'Mountain Bike',
        'status' => 'available',
        'condition' => 'good',
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.bikes.verify', $bike), [
            'action' => 'edit',
            'password' => 'secret-password',
        ])
        ->assertOk();

    $this->actingAs($admin, 'web')
        ->patch(route('admin.bikes.update', $bike), [
            'model' => 'Trail 200',
            'make' => 'Trek',
            'bike_type' => 'Mountain Bike',
            'status' => 'rented',
            'condition' => 'good',
            'current_tab' => 'bikes',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('bicycle', [
        'bike_id' => $bike->bike_id,
        'status' => 'available',
    ]);
});
