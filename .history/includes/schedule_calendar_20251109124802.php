<?php
require_once '../config.php';
require_once 'header.php'; // (ใช้ header.php ที่อยู่ใน includes/)

// (โค้ดนี้ไม่จำเป็นต้องใช้แล้ว เพราะเราไม่ใช้ Modal)
// $schedules_result = $conn->query("SELECT * FROM schedule_types ORDER BY id");
// $all_schedules = [];
// ...
?>

<style>
  /* ปรับความสูงของปฏิทิน */
  #calendar {
    height: 80vh;
  }

  /* (สไตล์สำหรับ Admin) */
  <?php if ($current_user_role == 'admin'): ?>
    .fc-resource-timeline-lane {
      border-bottom: 1px solid #eee !important;
    }

    .fc-datagrid-cell-cushion {
      display: flex;
      align-items: center;
      padding: 10px;
    }

    .fc-datagrid-cell-cushion img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      margin-right: 10px;
      object-fit: cover;
    }

  <?php endif; ?>

  /* สไตล์ของ "กะ" (Event) (ใช้ร่วมกัน) */
  .fc-event {
    border: none !important;
    padding: 4px 8px !important;
    font-weight: 500;
  }
</style>

<div class="container">
  <h3><i class="bi bi-calendar3 me-2"></i>
    <?php echo ($current_user_role == 'admin') ? 'ปฏิทินกะพนักงาน (Shift Calendar)' : 'ปฏิทินกะของฉัน'; ?>
  </h3>
  <hr>
  <div class="card shadow-sm">
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const userRole = '<?php echo $current_user_role; ?>';
    var calendarEl = document.getElementById('calendar');

    // *** (จุดที่ 1) กำหนดค่าพื้นฐานของปฏิทิน (ใช้ร่วมกัน) ***
    var calendarOptions = {
      locale: 'th',
      editable: false, // ปิดการลาก
      eventContent: function (arg) {
        // (ใช้ Logic นี้ร่วมกัน)
        let time = arg.event.extendedProps.time_range || '';
        return {
          html: `<b>${arg.event.title}</b><br><small>${time}</small>`
        };
      },
      // (สำคัญ) API สำหรับดึง "กะ" (Events)
      // API นี้จะถูกสร้างในขั้นตอนถัดไป
      events: '../api/api_get_schedules.php'
    };

    // *** (จุดที่ 2) ตรวจสอบสิทธิ์ และกำหนดค่าปฏิทินที่แตกต่างกัน ***
    if (userRole === 'admin') {

      // --- ADMIN OPTIONS ---
      Object.assign(calendarOptions, {
        initialView: 'resourceTimelineWeek', // (มุมมองบอร์ด)
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'resourceTimelineWeek,resourceTimelineDay'
        },

        // (สำคัญ) API สำหรับดึง "พนักงาน" (Resources)
        // API นี้จะถูกสร้างในขั้นตอนถัดไป
        resources: '../api/api_get_employees.php',

        resourceAreaHeaderContent: 'พนักงาน',
        resourceLabelContent: function (arg) {
          let imgHtml = '';
          if (arg.resource.extendedProps.image_url) {
            imgHtml = `<img src="${arg.resource.extendedProps.image_url}" alt="">`;
          } else {
            imgHtml = '<i class="bi bi-person-circle" style="font-size: 32px; margin-right: 10px; color: #ccc;"></i>';
          }
          return { html: imgHtml + arg.resource.title };
        }

        // (เราได้ลบ dateClick และ eventClick ออกไปแล้ว)

      });

    } else {

      // --- EMPLOYEE OPTIONS ---
      Object.assign(calendarOptions, {
        initialView: 'timeGridWeek', // (มุมมองปฏิทินสัปดาห์)
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'timeGridWeek,timeGridDay,dayGridMonth'
        }
      });
    }

    // *** (จุดที่ 3) สร้างปฏิทินด้วย Options ที่เลือกไว้ ***
    var calendar = new FullCalendar.Calendar(calendarEl, calendarOptions);
    calendar.render();

    // (เราได้ลบโค้ดสำหรับรับค่า Modal ออกไปแล้ว)
  });
</script>

<?php
$conn->close();
require_once 'footer.php'; // (ใช้ footer.php ที่อยู่ใน includes/)
?>