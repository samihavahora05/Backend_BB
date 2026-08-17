# Blueboxx DA backend fixes applied

This package contains targeted backend fixes based on the uploaded backend project.

## Fixed

1. Student registration no longer dispatches the welcome-email job, so registration does not require Gmail/SMTP.
2. Public registration always creates a `student` role; elevated roles cannot be selected from the public registration request.
3. Fixed the missing closing brace in `app/Models/Wishlist.php`.
4. Removed duplicate Admin MCQ analytics route declarations.
5. Added Student Virtual Class API endpoints and enrollment support.
6. Added Admin Virtual Class MCQ management endpoints.
7. Added a database relationship allowing a quiz/MCQ to be attached to a Virtual Class.
8. Added `passing_score` to the quiz schema, matching the existing quiz controllers/models.
9. Added Student Virtual Class MCQ viewing/submission endpoints.
10. Removed the hardcoded fake Google Meet URL from expert booking confirmation; a real provider-generated link must be supplied instead.
11. Moved development/debug/database/test PHP scripts out of the public web root into `_dev_tools_removed_from_public/`.
12. PHP syntax checks pass for application/config/database/routes/test PHP source files.

## Important deployment steps

Run migrations on the target database:

```bash
php artisan migrate
```

Clear cached configuration/routes after deployment:

```bash
php artisan optimize:clear
```

The uploaded `.env` has an empty `APP_KEY`. Generate/configure a valid key for a new local environment with:

```bash
php artisan key:generate
```

Do **not** rotate an existing production `APP_KEY` without planning for the effect on encrypted data/sessions.

## Registration email behavior

Password reset/other legitimate notification code was not globally removed. Only the registration welcome-email dispatch was removed, matching the requirement that any student can register without SMTP/email verification.

## Virtual Class API

Student endpoints are under the authenticated student prefix:

- `GET /api/student/virtual-classes`
- `GET /api/student/virtual-classes/{id}`
- `POST /api/student/virtual-classes/{id}/enroll`
- `GET /api/student/virtual-classes/{id}/quiz`
- `POST /api/student/virtual-classes/{id}/quiz/submit`

Admin MCQ endpoints are under the existing authenticated admin prefix:

- `GET /api/admin/virtual-classes/{virtualClassId}/quiz`
- `POST /api/admin/virtual-classes/{virtualClassId}/quiz`
- `DELETE /api/admin/virtual-classes/{virtualClassId}/quiz`
