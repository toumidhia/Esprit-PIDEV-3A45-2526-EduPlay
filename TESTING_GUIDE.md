# EduPlay Course & Seance Testing Guide

## Test Users Created

Run this command first to create test users:
```bash
php bin/console app:create-test-users
```

| Role | Email | Password | Purpose |
|------|-------|----------|---------|
| Teacher | teacher@eduplay.com | password123 | Creates courses |
| Admin | admin@eduplay.com | password123 | Approves/rejects courses, creates seances |
| Parent | parent@eduplay.com | password123 | Views accepted courses, subscribes kids |
| Kid | kid@eduplay.com | password123 | Views courses parent subscribed to |

---

## Complete Testing Scenario

### Phase 1: Teacher Creates Course (Pending Status)

**Route:** `http://127.0.0.1:8000/course/new`

**Steps:**
1. Navigate to the "new course" page
2. Fill in the form:
   - **Title:** Introduction to Mathematics
   - **Description:** Learn basic math concepts for beginners
   - **Duration:** 4 weeks
   - **Level:** Beginner
   - **PDF File:** Upload a sample PDF (optional)
3. Submit the form
4. Course is created with `status = 'pending'`

**Expected Result:**
- Flash message: "Course created successfully! It is pending admin approval."
- Course appears in course list with "Pending" badge
- Course is assigned to test teacher (teacher@eduplay.com)

---

### Phase 2: Admin Views All Courses

**Route:** `http://127.0.0.1:8000/course`

**Steps:**
1. Navigate to course index
2. View statistics dashboard showing:
   - Total courses
   - Pending courses (should show 1)
   - Accepted courses
   - Rejected courses

**Expected Result:**
- See the newly created course in "Pending" status
- Statistics correctly display counts

---

### Phase 3: Admin Approves Course

**Route:** `http://127.0.0.1:8000/course/{id}/accept`

**Steps:**
1. From course index, click "Accept" button on the pending course
2. Course status changes to "accepted"

**Expected Result:**
- Flash message: "Course accepted successfully!"
- Course badge changes from "Pending" (yellow) to "Accepted" (green)
- Course is now eligible for seance assignment

**Alternative:** Admin can reject course using:
- **Route:** `http://127.0.0.1:8000/course/{id}/reject`
- Course status becomes "rejected" (red badge)

---

### Phase 4: Admin Creates Seance for Accepted Course

**Route:** `http://127.0.0.1:8000/seance/new`

**Steps:**
1. Navigate back to course list (http://127.0.0.1:8000/course)
2. Find the accepted course - you'll see a blue "Create Seance" button
3. Click the "Create Seance" button (course will be pre-selected automatically)
   - **Alternative:** Navigate directly to http://127.0.0.1:8000/seance/new
4. Fill in the form:
   - **Course:** Already selected (or select "Introduction to Mathematics")
   - **Start Time:** 2026-02-10 10:00
   - **End Time:** 2026-02-10 12:00
5. Submit the form

**Expected Result:**
- Flash message: "Session created successfully and assigned to the course!"
- Seance appears in seance list
- Seance is linked to the accepted course

**Validation:**
- End time must be greater than start time
- Can only assign seances to accepted courses
- "Create Seance" button only appears for admin users on accepted courses

---

### Phase 5: Admin Views All Seances

**Route:** `http://127.0.0.1:8000/seance`

**Steps:**
1. Navigate to seance index
2. View statistics:
   - Total seances
   - Upcoming seances
   - Past seances

**Expected Result:**
- See newly created seance listed
- Statistics correctly categorize based on current date

---

### Phase 6: Parent Views Accepted Courses

**Route:** `http://127.0.0.1:8000/course?role=parent`

**Steps:**
1. Navigate to course index with parent role parameter
2. Only courses with status "accepted" AND assigned seances are visible
3. Each course card shows a "Subscribe Your Kids" section

**Expected Result:**
- Only accepted courses with seances are displayed
- Subscription interface available for each kid
- Subscribe/Unsubscribe buttons visible

---

### Phase 7: Parent Subscribes Kid to Course

**Route:** `http://127.0.0.1:8000/course?role=parent`

**Steps:**
1. Navigate to parent view: http://127.0.0.1:8000/course?role=parent
2. Find an accepted course with seances
3. In the "Subscribe Your Kids" section, click "Subscribe" button next to a kid's name (e.g., Tommy Smith)
4. Wait for success message

**Expected Result:**
- Flash message: "Tommy successfully subscribed to [Course Name]!"
- Button changes from "Subscribe" to "Subscribed" (gray)
- Kid now has access to the course

**To Unsubscribe:**
- Click the "Subscribed" button to unsubscribe the kid
- Flash message: "Subscription cancelled successfully."

---

### Phase 8: Kid Views Subscribed Courses

**Route:** `http://127.0.0.1:8000/course?role=kid`

**Steps:**
1. Navigate to course index with kid role parameter: http://127.0.0.1:8000/course?role=kid
2. Only courses that the parent subscribed the kid to are visible

**Expected Result:**
- Kid sees ONLY courses their parent subscribed them to
- If no subscriptions exist, shows "No Courses Found" message
- Kids cannot see courses until parent subscribes them

---

## All Available Routes

### Course Routes

| Method | Route | Name | Description | Role |
|--------|-------|------|-------------|------|
| GET | `/course` | `app_course_index` | List all courses with filters | All |
| GET | `/course/new` | `app_course_new` | Create new course form | Teacher |
| POST | `/course/new` | `app_course_new` | Submit new course | Teacher |
| GET | `/course/{id}` | `app_course_show` | View course details | All |
| GET | `/course/{id}/pdf` | `app_course_pdf` | Download course PDF | All |
| GET | `/course/{id}/edit` | `app_course_edit` | Edit course form | Teacher/Admin |
| POST | `/course/{id}/edit` | `app_course_edit` | Submit course edits | Teacher/Admin |
| POST | `/course/{id}` | `app_course_delete` | Delete course | Admin |
| POST | `/course/{id}/accept` | `app_course_accept` | Accept pending course | Admin |
| POST | `/course/{id}/reject` | `app_course_reject` | Reject pending course | Admin |
| POST | `/course/{id}/subscribe` | `app_course_subscribe` | Subscribe kid to course | Parent |
| POST | `/course/{id}/unsubscribe` | `app_course_unsubscribe` | Unsubscribe kid from course | Parent |

### Seance Routes

| Method | Route | Name | Description | Role |
|--------|-------|------|-------------|------|
| GET | `/seance` | `app_seance_index` | List all seances with filters | Admin |
| GET | `/seance/new` | `app_seance_new` | Create new seance form | Admin |
| POST | `/seance/new` | `app_seance_new` | Submit new seance | Admin |
| GET | `/seance/{id}` | `app_seance_show` | View seance details | Admin |
| GET | `/seance/{id}/edit` | `app_seance_edit` | Edit seance form | Admin |
| POST | `/seance/{id}/edit` | `app_seance_edit` | Submit seance edits | Admin |
| POST | `/seance/{id}` | `app_seance_delete` | Delete seance | Admin |

---

## Advanced Features Testing

### Filter Courses

**Route:** `http://127.0.0.1:8000/course?search=Math&status=accepted&level=Beginner&sort=title&order=ASC`

**Query Parameters:**
- `search` - Search in title/description
- `status` - Filter by status (pending/accepted/rejected)
- `level` - Filter by level (Beginner/Intermediate/Advanced)
- `duration` - Filter by duration
- `sort` - Sort by field (id/title/level/status/durationTraining)
- `order` - Sort direction (ASC/DESC)

### Filter Seances

**Route:** `http://127.0.0.1:8000/seance?search=Math&course=1&startDate=2026-02-01&endDate=2026-02-28&sort=startTime&order=DESC`

**Query Parameters:**
- `search` - Search in course title
- `course` - Filter by course ID
- `startDate` - Filter seances starting after this date
- `endDate` - Filter seances ending before this date
- `month` - Filter by month (YYYY-MM)
- `sort` - Sort by field (startTime/endTime)
- `order` - Sort direction (ASC/DESC)

### Download PDF

**Route:** `http://127.0.0.1:8000/course/{id}/pdf`

**Requirements:**
- Course must have a PDF file uploaded
- File must exist in `public/uploads/courses/`

---

## Quick Testing Sequence

1. **Start Symfony Server:**
   ```bash
   symfony server:start
   ```

2. **Create Test Users:**
   ```bash
   php bin/console app:create-test-users
   ```

3. **Navigate to create course:**
   - URL: http://127.0.0.1:8000/course/new
   - Fill form and su (Admin):**
   - URL: http://127.0.0.1:8000/course?role=admin
   - Verify course appears with "Pending" status

5. **Accept the course:**
   - Click "Accept" button on the pending course
   - Or navigate to: http://127.0.0.1:8000/course/1/accept (replace 1 with actual ID)

6. **Create seance:**
   - Click the blue "Create Seance" button on the accepted course
   - Or navigate directly to: http://127.0.0.1:8000/seance/new
   - Select the accepted course
   - Set start and end times

7. **View as Parent:**
   - URL: http://127.0.0.1:8000/course?role=parent
   - Only accepted courses with seances are shown
   - Subscribe Tommy to a course

8. **View as Kid:**
   - URL: http://127.0.0.1:8000/course?role=kid
   - Only subscribed courses are shown

9. **Test filtering:**
   - Add query parameters to filter courses or seances
   - Test sorting by clicking column headers

---

## Role-Based Testing URLs

To simulate different users, add `?role=` parameter:

- **Admin View:** http://127.0.0.1:8000/course?role=admin
- **Teacher View:** http://127.0.0.1:8000/course?role=teacher
- **Parent View:** http://127.0.0.1:8000/course?role=parent
- **Kid View:** http://127.0.0.1:8000/course?role=kid
   - Add query parameters to filter courses or seances
   - Test sorting by clicking column headers

---

## Common Issues & Solutions

### Error: "Column 'teacher_id_id' cannot be null"
**Solution:** Run `php bin/console app:create-test-users` to create test users

### Error: "Test teacher not found"
**Solution:** Ensure test users command ran successfully and database has the teacher user

### Error: "You can only assign sessions to accepted courses"
**Solution:** Make sure the course status is "accepted" before creating a seance

### Error: "PDF file not found"
**Solution:** Ensure the PDF was uploaded and exists in `public/uploads/courses/`

---


Check subscriptions:
```sql
SELECT s.id, 
       p.first_name as parent_name, 
       k.first_name as kid_name, 
       c.title as course_title,
       s.subscribed_at, with login/logout
   - Remove `?role=` URL parameters
   - Use `$this->getUser()` instead of test users
   - Uncomment `$this->denyAccessUnlessGranted()` calls

2. **Enhance Parent-Kid Relationship:**
   - Add a parent_id field to User entity
   - Filter kids to show only those belonging to logged-in parent
   - Implement kid registration by parent

3. **Add Password Hashing:**
   - Use `PasswordHasherInterface` in User entity
   - Hash passwords before storing

4. **Improve File Upload Security:**
   - Validate file types more strictly
   - Scan for malware
   - Implement file size limits

5. **Add Pagination:**
   - Implement Doctrine Paginator
   - Add page navigation to index templates

6. **Email Notifications:**
   - Notify teachers when course is accepted/rejected
   - Notify parents when kid is subscribed
   - Notify kids when new course is available
```sql
SELECT id, start_time, end_time, course_id_id FROM seance;
```

---

## Next Steps for Production

1. **Implement Symfony Security Component:**
   - Configure `security.yaml`
   - Create user authentication
   - Uncomment `$this->denyAccessUnlessGranted()` calls

2. **Implement Parent Subscription:**
   - Create `Subscription` entity
   - Add subscribe/unsubscribe routes
   - Filter kid's courses based on parent subscriptions

3. **Add Password Hashing:**
   - Use `PasswordHasherInterface` in User entity
   - Hash passwords before storing

4. **Improve File Upload Security:**
   - Validate file types more strictly
   - Scan for malware
   - Implement file size limits

5. **Add Pagination:**
   - Implement Doctrine Paginator
   - Add page navigation to index templates
