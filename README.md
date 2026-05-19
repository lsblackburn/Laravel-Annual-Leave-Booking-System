# Prime Creative Intranet

This intranet provides an annual leave management system for employees and administrators. It handles leave requests, allowance tracking, department coverage rules, working-day configuration, non-work days, calendar visibility, and leave notifications.

## Annual Leave System

The annual leave module is designed around three main users:

- Employees request, edit, and cancel their own leave.
- Admins review leave requests and manage employee records.
- The system calculates allowance usage using the configured leave rules.

## Employee Guide

### View Your Leave

Employees can view their annual leave from the **Your Annual Leave** page.

This page shows:

- Total leave allowance
- Remaining leave allowance
- Pending leave awaiting admin review
- Previous leave requests
- Request status: pending, approved, or rejected
- Manager comments on reviewed requests

### Submit a Leave Request

To submit leave:

1. Open **Your Annual Leave**.
2. Select **Create new leave request**.
3. Choose a start date and end date.
4. Select whether the request is a half day.
5. Enter a reason for the request.
6. Add optional supporting information.
7. Submit the request.

The system checks the request before saving it. A request may be rejected immediately if:

- The start date is in the past.
- The end date is before the start date.
- A half-day request covers more than one date.
- The request contains no working days.
- The request exceeds the employee's available allowance.
- The request would leave the employee's department without cover.

### Edit or Cancel Leave

Employees can only edit or cancel leave while it is still **pending**.

Once a request has been approved or rejected, it can no longer be changed by the employee.

## Admin Guide

### Review Leave Requests

Admins can review pending leave from **Admin > Leave Requests**.

For each request, admins can:

- Review the employee name
- Review the leave dates
- See whether the request is full day or half day
- Read the reason and additional information
- Add a manager comment
- Approve or decline the request

When a request is approved, the system re-checks allowance and department coverage before saving the approval. This prevents stale pending requests from being approved if the employee no longer has enough allowance or department cover has changed.

### Manage Users

Admins can manage employees from **Admin > Users**.

Admins can:

- Create users
- Edit user profile details
- Assign departments
- Set employment start dates
- Update user passwords
- Promote employees to admin
- Demote admins to employee
- Delete users

Admins cannot edit, demote, delete, or change their own password through the admin user management screens.

### Manage Departments

Admins can manage company departments from **Admin > App Configuration > Company Departments**.

Departments are used by the coverage rule. If a department has more than one employee, the system requires at least one department member to remain available on each working day.

If a department has only one employee, the coverage rule does not block that employee from requesting leave.

### Configure Leave Rules

Admins can manage leave rules from **Admin > App Configuration > Leave Rules**.

This includes:

- Annual leave refresh date
- Base allowance
- Allowance increases after years of service
- Maximum allowance
- Active working days
- Non-work days such as bank holidays or company closure days

## Allowance Rules

Leave allowance is calculated using the configured leave settings.

The system supports:

- A base annual allowance
- Increases after a configured number of years
- A maximum allowance cap
- Refresh dates such as April 1 or January 1
- Leap-day refresh handling for February 29

Allowance calculations only count working days.

The following do not reduce allowance:

- Inactive working days, such as weekends if weekends are disabled
- Configured non-work days, such as bank holidays

Pending leave reserves allowance so that an employee cannot submit several separate pending requests that would overspend their balance if all were approved.

If pending leave is cancelled by the employee or declined by an admin, those reserved days become available again.

## Working Days

Admins can choose which days of the week are working days.

For example, if Monday to Friday are active and Saturday/Sunday are inactive:

- Leave booked Monday to Friday uses allowance.
- Leave booked only on Saturday or Sunday is rejected because it contains no working days.
- Department coverage is not checked on inactive days.

## Non-Work Days

Admins can add dated non-work days, such as:

- Bank holidays
- Company closure days
- Office shutdown days

Non-work days:

- Do not reduce employee allowance.
- Appear on the calendar.
- Are ignored by department coverage checks.
- Can be added for dates in the current allowance year so past corrections can still be made.

The system prevents duplicate non-work days on the same date.

## Department Coverage Rule

The system prevents leave from being accepted if it would leave a department with nobody available on a working day.

The rule applies when:

- The employee belongs to a department.
- The department has more than one employee.
- The requested date is a working day.
- The date is not a configured non-work day.

The rule does not apply when:

- The employee has no department.
- The department only has one employee.
- The requested date is not a working day.
- The requested date is a configured non-work day.

## Calendar

The calendar shows approved annual leave and configured non-work days.

Approved leave appears as employee leave events. Non-work days appear separately so users can see public holidays and company closures alongside annual leave.

## Notifications

The system sends in-app notifications and email notifications.

Admins receive a notification when an employee submits a leave request.

Employees receive a notification when an admin approves or declines their leave request.

In-app notifications appear in the navigation notification menu. Opening a notification marks it as read and takes the user to the relevant page.

Email notifications are queued, so a queue worker must be running for emails to send.

## Two-Factor Authentication

Users can enable two-factor authentication from their profile.

When enabled, login requires a valid one-time code after the password step.

## Local Development

Install dependencies:

```bash
composer install
npm install
```

Create environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Start the development server:

```bash
php artisan serve
```

Build frontend assets:

```bash
npm run dev
```

Run queued jobs for email notifications:

```bash
php artisan queue:work
```

Run tests:

```bash
php artisan test
```

## Operational Notes

The allowance sync command is registered in `routes/console.php` as a daily scheduled task.

In production, Laravel's scheduler must be invoked every minute by cron so it can automatically run due tasks:

```bash
* * * * * cd /path/to/prime-creative-intranet && php artisan schedule:run >> /dev/null 2>&1
```

For local development, you can keep the scheduler running with:

```bash
php artisan schedule:work
```

To manually test the allowance sync command, run:

```bash
php artisan leave:sync-allowances
```

On the configured leave refresh date, the scheduled task recalculates user allowances based on the current leave rules and employment start dates.

For production, ensure the following are configured:

- Database connection
- Mail transport
- Queue worker
- Scheduler
- Application URL
- HTTPS
