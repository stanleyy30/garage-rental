# Garage Rental

A web app for renting garages for bicycles, cars and electric cars. Tenants register, wait until the admin accepts them, then rent a garage and upload a payment proof. The admin creates garages, verifies new tenants, extends rentals and reads income reports.

Built with Laravel 13, PHP 8.4, MySQL, Blade and Bootstrap 5.

## Run it locally

You need PHP 8.3 or newer, Composer and MySQL.

1. Create an empty MySQL database named `garage_rental`.
2. Install the packages and create your settings file:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

3. Check the database lines in `.env`. They expect the user `root` with no password on 127.0.0.1.
4. Create the tables and the admin account, then start the server:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

5. Open http://127.0.0.1:8000 and log in as the admin with `admin@garage.test` and `admin12345`. Tenants sign up with the link on the login page.

## Business rules

| Rule | How it works |
|---|---|
| Garage IDs | S for bicycle, C for car and E for electric car garages, numbered from 001. All types together are capped at 999 garages. |
| Which garage fits | Bicycles and motorcycles use S garages, petrol cars use C garages, electric cars use C or E garages. |
| Registration | NIK (16 digits), name, phone, email, password and a photo of the KTP. The admin compares the KTP photo with the typed NIK and accepts or rejects the tenant; only accepted tenants can rent. |
| Booking | Only tenants book, on the website. The start date is always today: the calendar greys out every other day and the server rejects other dates. |
| Daily rental | Starts today and lasts 1 day up to the number of days in this month minus one. Costs the daily price times the days. |
| Monthly rental | Lasts as many days as the start month has (15 Feb 2026 to 15 Mar 2026). Costs 25 times the daily price. |
| Battery charging | Only for an electric car in an E garage. The tenant enters the battery size, the remaining battery (0 to 99%) and the kWh until full. Charging is billed once: kWh until full times the garage's price per kWh. |
| Extension by the tenant | In the app, with the same limits as a new rental, counted from the current end date: daily up to that month's days minus one, or one month. The request waits until the admin uploads the payment evidence and accepts it, or rejects it. Only accepted extensions move the end date and count in the reports. |
| Extension by the admin | For any other length the tenant asks the admin for a number of days. Whole months from the current end date cost the monthly price and the remaining days cost the daily price. From 30 Nov, 31 days is one month plus one day. The admin uploads the payment evidence and the extension counts at once. |
| Payment proof | A jpg or png image of at most 1 MB, for every rental and extension. |
| Garage status | A garage is used while a rental covers today and becomes available again after the end date. |

## Where the code lives

The app follows MVC. Models in `app/Models` only describe the tables, their casts and relationships. Controllers in `app/Http/Controllers` run every query, calculation and save. The formulas sit in `Concerns/CalculatesRentals.php` and the shared extension steps in `Concerns/HandlesExtensions.php`, so the tenant and admin pages use the same code. Form requests in `app/Http/Requests` hold the validation rules. Views in `resources/views` only display the values the controllers pass to them.

## Tests

```bash
php artisan test
```

76 tests run against an in-memory SQLite database, so they never touch your MySQL data. Unit tests check every formula; feature tests check each page and rule from registration to reports.
