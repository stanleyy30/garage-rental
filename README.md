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
| Garage IDs | S for bicycle, C for car and E for electric car garages, numbered from 001. All types together are capped at 999 garages. The price per kWh is only asked for electric car garages. |
| Which garage fits | Bicycles and motorcycles use S garages, petrol cars use C garages, electric cars use C or E garages. |
| Registration | NIK (16 digits), name (letters only), phone (10 to 15 digits starting with 08), email, password (8+ characters) and a photo of the KTP. Every wrong field is shown in red at once. The admin opens the KTP photo in a viewer that can turn it, compares it with the typed NIK and accepts or rejects the tenant; only accepted tenants can rent. A rejected tenant can correct the details, upload a new KTP photo and send the registration again. |
| Booking | Only tenants book, on the website. The start date is today or any later day, picked on a one-month calendar that greys out past days and marks the whole booking from start to end. Garages that are booked on the chosen days are greyed out in the list with their booked dates; the rest can be picked. A tenant can rent several garages at the same time. Each vehicle has its own plate number: a plate can't belong to two tenants or be in two garages on the same day. |
| Daily rental | Lasts 1 day up to the number of days in the start month minus one (27 in February 2026, 30 in a 31-day month). Costs the daily price times the days. |
| Monthly rental | Lasts as many days as the start month has (15 Feb 2026 to 15 Mar 2026). Costs 25 times the daily price. No number of days is asked. |
| Battery charging | Only for an electric car in an E garage; the charging part of the form appears once an E garage is chosen. The tenant enters the battery size and the kWh left in the battery. The app works out the percent left and the kWh until full (6.7 of 26.7 kWh = 25%, 20 kWh until full). Charging is billed once: kWh until full times the garage's price per kWh. |
| Extension by the tenant | In the app, with the same limits as a new rental, counted from the current end date: daily up to that month's days minus one, or one month. The vehicle stays in the same garage; only the end date moves. The request waits until the admin uploads the payment evidence and accepts it, or rejects it. Only accepted extensions move the end date and count in the reports. The tenant sees on My Rentals and the dashboard whether a request was accepted or rejected. |
| Extension by the admin | For any other length the tenant asks the admin in person. The admin finds the renter's garage by searching their name on Extend Garage Rental, then picks the new end date on a calendar or enters the number of extra days. Whole months from the current end date cost the monthly price and the remaining days cost the daily price. From 30 Nov, 31 days is one month plus one day. The date and the days follow each other, and the calendar marks the extra days. The admin uploads the payment evidence, the extension counts at once and the tenant is told. An extension can't run into another tenant's later booking of the same garage. |
| Payment proof | A jpg or png image of at most 1 MB, for every rental and extension. |
| Garage status | The end date is the day the vehicle leaves. A garage is used from the start date until the day before the end date, and free again on the end date, so a new booking may start on the day another ends. A garage that is booked later can still be booked before that booking and after it. |
| Confirm pages | Show the daily price and the cost formula. "Change details" goes back with everything still filled in, and confirming without the payment proof marks the field in red. |

## Where the code lives

The app follows MVC. Models in `app/Models` only describe the tables, their casts and relationships. Controllers in `app/Http/Controllers` run every query, calculation and save. The formulas sit in `Concerns/CalculatesRentals.php` and the shared extension steps in `Concerns/HandlesExtensions.php`, so the tenant and admin pages use the same code. Form requests in `app/Http/Requests` hold the validation rules. Views in `resources/views` only display the values the controllers pass to them.

## Tests

```bash
php artisan test
```

98 tests run against an in-memory SQLite database, so they never touch your MySQL data. Unit tests check every formula; feature tests check each page and rule from registration to reports.
