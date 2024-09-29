# [Liberu CRM](https://www.liberu.org.uk) ![Open Source Love](https://img.shields.io/badge/Open%20Source-%E2%9D%A4-red.svg)

![](https://img.shields.io/badge/PHP-8.3-informational?style=flat&logo=php&color=4f5b93)
![](https://img.shields.io/badge/Laravel-11-informational?style=flat&logo=laravel&color=ef3b2d)
![](https://img.shields.io/badge/Filament-3.2-informational?style=flat&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgeG1sbnM6dj0iaHR0cHM6Ly92ZWN0YS5pby9uYW5vIj48cGF0aCBkPSJNMCAwaDQ4djQ4SDBWMHoiIGZpbGw9IiNmNGIyNWUiLz48cGF0aCBkPSJNMjggN2wtMSA2LTMuNDM3LjgxM0wyMCAxNWwtMSAzaDZ2NWgtN2wtMyAxOEg4Yy41MTUtNS44NTMgMS40NTQtMTEuMzMgMy0xN0g4di01bDUtMSAuMjUtMy4yNUMxNCAxMSAxNCAxMSAxNS40MzggOC41NjMgMTkuNDI5IDYuMTI4IDIzLjQ0MiA2LjY4NyAyOCA3eiIgZmlsbD0iIzI4MjQxZSIvPjxwYXRoIGQ9Ik0zMCAxOGg0YzIuMjMzIDUuMzM0IDIuMjMzIDUuMzM0IDEuMTI1IDguNUwzNCAyOWMtLjE2OCAzLjIwOS0uMTY4IDMuMjA5IDAgNmwtMiAxIDEgM2gtNXYyaC0yYy44NzUtNy42MjUuODc1LTcuNjI1IDItMTFoMnYtMmgtMnYtMmwyLTF2LTQtM3oiIGZpbGw9IiMyYTIwMTIiLz48cGF0aCBkPSJNMzUuNTYzIDYuODEzQzM4IDcgMzggNyAzOSA4Yy4xODggMi40MzguMTg4IDIuNDM4IDAgNWwtMiAyYy0yLjYyNS0uMzc1LTIuNjI1LS4zNzUtNS0xLS42MjUtMi4zNzUtLjYyNS0yLjM3NS0xLTUgMi0yIDItMiA0LjU2My0yLjE4N3oiIGZpbGw9IiM0MDM5MzEiLz48cGF0aCBkPSJNMzAgMThoNGMyLjA1NSA1LjMxOSAyLjA1NSA1LjMxOSAxLjgxMyA4LjMxM0wzNSAyOGwtMyAxdi0ybC00IDF2LTJsMi0xdi00LTN6IiBmaWxsPSIjMzEyODFlIi8+PHBhdGggZD0iTTI5IDI3aDN2MmgydjJoLTJ2MmwtNC0xdi0yaDJsLTEtM3oiIGZpbGw9IiMxNTEzMTAiLz48cGF0aCBkPSJNMzAgMThoNHYzaC0ydjJsLTMgMSAxLTZ6IiBmaWxsPSIjNjA0YjMyIi8+PC9zdmc+&&color=fdae4b&link=https://filamentphp.com)
![Jetstream](https://img.shields.io/badge/Jetstream-5-purple.svg)
![Socialite](https://img.shields.io/badge/Socialite-latest-brightgreen.svg)
![](https://img.shields.io/badge/Livewire-3.5-informational?style=flat&logo=Livewire&color=fb70a9)
![](https://img.shields.io/badge/JavaScript-ECMA2020-informational?style=flat&logo=JavaScript&color=F7DF1E)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)


[![Install](https://github.com/liberu-crm/crm-laravel/actions/workflows/install.yml/badge.svg)](https://github.com/liberu-crm/crm-laravel/actions/workflows/install.yml)
[![Tests](https://github.com/liberu-crm/crm-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/liberu-crm/crm-laravel/actions/workflows/tests.yml)
[![Docker](https://github.com/liberu-crm/crm-laravel/actions/workflows/main.yml/badge.svg)](https://github.com/liberu-crm/crm-laravel/actions/workflows/main.yml)



## [Hosted application packages](https://liberu.co.uk/order/main/packages/applications/?group_id=3)



## Our Projects

* https://github.com/liberu-accounting/accounting-laravel
* https://github.com/liberu-automation/automation-laravel
* https://github.com/liberu-billing/billing-laravel
* https://github.com/liberusoftware/boilerplate
* https://github.com/liberu-browser-game/browser-game-laravel
* https://github.com/liberu-cms/cms-laravel
* https://github.com/liberu-control-panel/control-panel-laravel
* https://github.com/liberu-crm/crm-laravel
* https://github.com/liberu-ecommerce/ecommerce-laravel
* https://github.com/liberu-genealogy/genealogy-laravel
* https://github.com/liberu-maintenance/maintenance-laravel
* https://github.com/liberu-real-estate/real-estate-laravel
* https://github.com/liberu-social-network/social-network-laravel

## Setup

1. Ensure your environment is set up with PHP 8.3 and Composer installed.
2. Download the project files from this GitHub repository.
3. Open a terminal in the project folder. If you are on Windows and have Git Bash installed, you can use it for the following steps.
4. Run the following command:

```bash
./setup.sh
```

and everything should be installed automatically if you are using Linux you just run the script as you normally run scripts in the terminal.

NOTE 1: The script will ask you if you want to have your .env be overwritten by .env.example, in case you have already an .env configuration available please answer with "n" (No).

NOTE 2: This script will run seeders, please make sure you are aware of this and don't run this script if you don't want this to happen.
```bash
composer install
php artisan key:generate
php artisan migrate --seed
```
This will install the necessary dependencies, generate an application key, and set up your database with initial data.

NOTE 3: Ensure your `.env` file is correctly configured with your database connection details before running migrations.

## Building with Docker

Alternatively, you can build and run the project using Docker. To build the Dockerfile, follow these steps:

1. Ensure you have Docker installed on your system.
2. Open a terminal in the project folder.
3. Run the following command to build the Docker image:
   ```
   docker build -t crm-laravel .
   ```
4. Once the image is built, you can run the container with:
   ```
   docker run -p 8000:8000 crm-laravel
   ```

NOTE 3: Ensure your `.env` file is correctly configured with your database connection details before running migrations.

### Using Laravel Sail

This project also includes support for Laravel Sail, which provides a Docker-based development environment. To use Laravel Sail, follow these steps:

1. Ensure you have Docker installed on your system.
2. Open a terminal in the project folder.
3. Run the following command to start the Laravel Sail environment:
   ```
   ./vendor/bin/sail up
   ```
4. Once the containers are running, you can access the application at `http://localhost`.
5. To stop the Sail environment, press `Ctrl+C` in the terminal.

For more information on using Laravel Sail, refer to the [official documentation](https://laravel.com/docs/sail).


### Description
Welcome to Liberu CRM, our innovative open-source project that reimagines Contact Relationship Management with the power of Laravel 11, PHP 8.3, Livewire 3, and Filament 3. Liberu CRM isn't just a tool for managing contacts; it's a dynamic platform designed to foster meaningful connections, streamline interactions, and elevate the way relationships are nurtured and maintained.

**Key Features:**

1. **Seamless Contact Management:** Liberu CRM offers a user-friendly interface for efficient contact management. From customer profiles to lead tracking, our project ensures that every interaction is captured, organized, and easily accessible.

2. **Dynamic Livewire Interactions:** Built on Laravel 11 and PHP 8.3, Liberu CRM integrates Livewire 3 for dynamic and real-time interactions. Enhance your communication and collaboration by updating contact information, notes, and activities in real time.

3. **Efficient Admin Panel:** Filament 3, our admin panel built on Laravel, provides administrators with powerful tools to manage users, customize settings, and oversee the entire contact ecosystem. Ensure that your CRM operates seamlessly, adapting to your organization's evolving needs.

4. **Customizable Forms:** Tailor contact forms to capture specific information relevant to your business or industry. Liberu CRM empowers users to create custom forms that align with their unique requirements, ensuring comprehensive data collection.

5. **Task and Activity Tracking:** Stay organized with Liberu CRM's task and activity tracking features. Manage appointments, follow-ups, and deadlines efficiently, ensuring that important engagements are never overlooked.

Liberu CRM is open source, released under the permissive MIT license. We invite businesses, developers, and organizations to contribute to the evolution of Contact Relationship Management. Together, let's redefine the standards of relationship-building and create a platform that adapts to the unique needs of every user.

Welcome to Liberu CRM – where innovation meets connection, and the management of meaningful relationships is at the forefront. Join us on this journey to transform the way we cultivate and nurture connections in the digital age.

### Licensed under MIT, use for any personal or commercial project.

### Contributions

We warmly welcome new contributions from the community! We believe in the power of collaboration and appreciate any involvement you'd like to have in improving our project. Whether you prefer submitting pull requests with code enhancements or raising issues to help us identify areas of improvement, we value your participation.

If you have code changes or feature enhancements to propose, pull requests are a fantastic way to share your ideas with us. We encourage you to fork the project, make the necessary modifications, and submit a pull request for our review. Our team will diligently review your changes and work together with you to ensure the highest quality outcome.

However, we understand that not everyone is comfortable with submitting code directly. If you come across any issues or have suggestions for improvement, we greatly appreciate your input. By raising an issue, you provide valuable insights that help us identify and address potential problems or opportunities for growth.

Whether through pull requests or issues, your contributions play a vital role in making our project even better. We believe in fostering an inclusive and collaborative environment where everyone's ideas are valued and respected.

We look forward to your involvement, and together, we can create a vibrant and thriving project. Thank you for considering contributing to our community!

## Testing

Run `php artisan test` to execute the test suite, including Twilio integration tests.
<!--/h-->

### License

This project is licensed under the MIT license, granting you the freedom to utilize it for both personal and commercial projects. The MIT license ensures that you have the flexibility to adapt, modify, and distribute the project as per your needs. Feel free to incorporate it into your own ventures, whether they are personal endeavors or part of a larger commercial undertaking. The permissive nature of the MIT license empowers you to leverage this project without any unnecessary restrictions. Enjoy the benefits of this open and accessible license as you embark on your creative and entrepreneurial pursuits.
<!--/h-->

## Twilio Integration

### Setup

1. Sign up for a Twilio account at https://www.twilio.com/
2. Obtain your Twilio Account SID, Auth Token, and a Twilio phone number
3. Add the following environment variables to your `.env` file:

```
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_PHONE_NUMBER=your_twilio_phone_number
TWILIO_TWIML_APP_SID=your_twiml_app_sid
TWILIO_WEBHOOK_URL=https://your-app-url.com/twilio/webhook
```

4. Run `php artisan migrate` to create the necessary database tables for call logging

### Features

- Make outbound calls directly from the CRM interface
- Receive inbound calls and route them to the appropriate agent
- Click-to-call functionality for contact phone numbers
- Automatic call logging for all inbound and outbound calls
- Call recording capabilities
- Add notes to call logs for future reference

### Usage

To use the Twilio integration:

1. Navigate to a contact's profile
2. Click on the "Call" button next to their phone number
3. Use the call management interface to control the call, start/stop recording, and add notes
4. After the call, view the call log in the contact's activity timeline

## Usage

### Reporting and Analytics

The CRM now includes enhanced reporting and analytics capabilities. To access these features:

1. Navigate to the Analytics Dashboard:
   - Go to `/analytics-dashboard` to view key metrics and trends.

2. Generate Custom Reports:
   - Visit `/reports/contact-interactions` for Contact Interactions report
   - Visit `/reports/sales-pipeline` for Sales Pipeline report
   - Visit `/reports/customer-engagement` for Customer Engagement report

3. Customize Reports:
   - Use the Report Customizer component to filter and tailor reports to your needs.

4. Data Visualization:
   - The Analytics Dashboard includes interactive charts and graphs for better data interpretation.

5. Export Reports:
   - Each report page includes options to export data in various formats (CSV, PDF, Excel).

For more detailed information on using the reporting and analytics features, please refer to the user guide in the `docs` folder.

### Task Reminders and Google Calendar Integration

Liberu CRM now includes a powerful task reminder system with Google Calendar integration:

1. Creating Tasks with Reminders:
   - When creating or editing a task, you can set a reminder date and time.
   - Choose to sync the task with Google Calendar by toggling the "Sync to Google Calendar" option.

2. Receiving Reminders:
   - You'll receive email notifications for task reminders at the specified time.
   - Reminders are also visible in the CRM's notification center.

3. Google Calendar Sync:
   - To enable Google Calendar sync, go to your user settings and connect your Google account.
   - Tasks synced with Google Calendar will appear in your Google Calendar and update automatically when changed in the CRM.

4. Managing Reminders:
   - View all upcoming reminders in the "My Reminders" section of the dashboard.
   - Mark reminders as complete or snooze them for later.

For more information on using the task reminder system and Google Calendar integration, please refer to the user guide in the `docs` folder.

# CRM Laravel

## OAuth Configuration

This application now supports configuring OAuth settings for social media accounts, advertising accounts, Mailchimp, WhatsApp Business, and Facebook Messenger directly through the browser interface.

### Setting up OAuth Configurations

1. Log in to the admin panel.
2. Navigate to the OAuth Configurations section.
3. Click on "New OAuth Configuration" to add a new provider.
4. Fill in the required information:
   - Service Name (e.g., facebook, google, mailchimp)
   - Client ID
   - Client Secret
   - Additional Settings (if required)
5. Save the configuration.

### Using OAuth in the Application

Once configured, the application will automatically use the database-stored OAuth settings for authentication and API interactions with the respective services.

### Fallback to Environment Variables

If a configuration is not found in the database, the application will fall back to using the settings defined in the .env file.

### Supported Services

- Facebook
- Google
- Mailchimp
- WhatsApp Business
- Facebook Messenger
- (Add other supported services here)

For more detailed information on setting up each service, please refer to their respective documentation.

## Contributors


<a href = "https://github.com/liberu-crm/crm-laravel/graphs/contributors">
  <img src = "https://contrib.rocks/image?repo=liberu-crm/crm-laravel"/>
