<?php
// สมมติว่าไฟล์ config.php อยู่ในระดับ root และถูก include อัตโนมัติ
// หรือคุณอาจจะต้อง include เอง:
require_once __DIR__ . '/../config.php'; // <-- เอา // ออกจากหน้บรรทัดนี้

// header.php จะจัดการเรื่อง session และ $BASE_URL
require_once __DIR__ . '/header.php';
?>


<div class="card shadow-sm">
  <div class="card-header">
    <h3 class="mb-0">📅 ปฏิทินกะการทำงาน</h3>
  </div>
  <div class="card-body">
    <div id="calendar"></div>
  </div>
</div>

<?php
// Include footer ของคุณ (ถ้ามี)
// require_once __DIR__ . '/footer.php'; 
?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
      // --- การตั้งค่า Scheduler ---
      schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source', // หรือ 'YOUR_PREMIUM_KEY'

      // --- มุมมอง (Views) ---
      initialView: 'resourceTimelineWeek', // เริ่มต้นที่มุมมอง Timeline รายสัปดาห์
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth'
      },

      // --- การตั้งค่าภาษาและเวลา ---
      locale: 'th', // ใช้ภาษาไทย (จาก header.php ที่คุณ include ไว้)
      timeZone: 'Asia/Bangkok',

      // --- การดึงข้อมูล (สำคัญ!) ---

      // 1. ดึงข้อมูล "Resources" (พนักงาน) จาก API
      // กฎ Admin/Employee จะถูกจัดการใน API นี้
      resources: '<?php echo BASE_URL; ?>/includes/api_get_resources.php',

      // 2. ดึงข้อมูล "Events" (กะ) จาก API
      // กฎ Admin/Employee จะถูกจัดการใน API นี้
      events: '<?php echo BASE_URL; ?>/includes/api_get_schedules.php',

      // --- การแสดงผล ---
      editable: false, // ไม่ให้ลาก-วาง แก้ไข
      resourceAreaHeaderContent: 'พนักงาน',
      slotMinTime: '00:00:00', // เริ่มที่เที่ยงคืน
      slotMaxTime: '24:00:00', // สิ้นสุดที่เที่ยงคืน
      // ทำให้ Timeline Week แสดง 7 วันเสมอ
      duration: { weeks: 1 },
      // ทำให้แสดงชื่อ Resource (พนักงาน) ชัดเจนขึ้น
      resourceAreaWidth: '20%'
    });

    calendar.render();
  });
</script>

</body>

</html>