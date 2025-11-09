<?php
require_once '../config.php';
require_once 'header.php'; // (ใช้ header.php ที่อยู่ใน includes/)

// (ย้ายโค้ดดึงกะ มาไว้ตรงนี้ เพื่อใช้กับ Modal)
$schedules_result = $conn->query("SELECT * FROM schedule_types ORDER BY id");
$all_schedules = [];
while ($schedule = $schedules_result->fetch_assoc()) {
  $all_schedules[] = $schedule;
}
?>

<style>
  /* ปรับความสูงของปฏิทิน */
  #calendar {
    height: 80vh;
  }

  /* (สไตล์สำหรับ Admin) */
  <?php if ($current_user_role == 'admin'): ?>
    /* สไตล์ของ "แถว" พนักงาน (Resource) */
    .fc-resource-timeline-lane {
      border-bottom: 1px solid #eee !important;
    }

    /* (ใหม่) แสดงรูปโปรไฟล์ในแถว */
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

    /* (ใหม่) ทำให้ปุ่ม + (Add Employee) ในรูปของคุณ เป็นจริง (โดยใช้ CSS) */
    .fc-timeline-slot-cushion {
      /* ช่องว่างในปฏิทิน */
      transition: background-color 0.2s;
    }

    .fc-timeline-slot-cushion:hover {
      background-color: rgba(0, 123, 255, 0.05);
      /* สีฟ้าอ่อนๆ ตอน hover */
      cursor: pointer;
    }

  <?php endif; ?>

  /* สไตล์ของ "กะ" (Event) (ใช้ร่วมกัน) */
  .fc-event {
    border: none !important;
    padding: 4px 8px !important;
    font-weight: 500;
    cursor: pointer;
  }
</style>

<div class="container">
  <h3><i class="bi bi-calendar3 me-2"></i>
    <?php echo ($current_user_role == 'admin') ? 'บอร์ดจัดการกะ (Shift Board)' : 'ปฏิทินกะของฉัน'; ?>
  </h3>
  <hr>
  <div class="card shadow-sm">
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<?php if ($current_user_role == 'admin'): ?>
  <div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="editShiftForm">
          <div class="modal-header">
            <h5 class="modal-title" id="editShiftModalLabel">กำหนดกะการทำงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>พนักงาน: <strong id="editShiftEmployeeName"></strong></p>
            <p>วันที่: <strong id="editShiftDate"></strong></p>

            <input type="hidden" name="user_id" id="editShiftUserId">
            <input type="hidden" name="shift_date" id="editShiftDateInput">

            <div class="mb-3">
              <label for="schedule_id" class="form-label">เลือกกะ:</label>
              <select class="form-control" name="schedule_id" id="schedule_id" required>
                <option value="0">-- 0. ไม่กำหนดกะ (ลบกะ) --</option>
                <?php foreach ($all_schedules as $schedule): ?>
                  <option value="<?php echo $schedule['id']; ?>">
                    <?php echo htmlspecialchars($schedule['name'] . ' (' . date('H:i', strtotime($schedule['time_in'])) . ' - ' . date('H:i', strtotime($schedule['time_out'])) . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="editShiftResult" class="mt-3"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึก</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const userRole = '<?php echo $current_user_role; ?>';
    var calendarEl = document.getElementById('calendar');
    var editModal = null; // (จะถูกกำหนดค่าถ้าเป็น Admin)

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
      // เรา "ต้อง" สมมติว่า API นี้จะกรองข้อมูลให้เรา
      // ถ้าเป็น Admin -> ส่งกะของทุกคน
      // ถ้าเป็น Employee -> ส่งกะของ user_id ที่ล็อกอินอยู่เท่านั้น
      events: '../api/api_get_schedules.php'
    };

    // *** (จุดที่ 2) ตรวจสอบสิทธิ์ และกำหนดค่าปฏิทินที่แตกต่างกัน ***
    if (userRole === 'admin') {

      // --- ADMIN OPTIONS ---

      // (เปิดใช้งาน Modal)
      editModal = new bootstrap.Modal(document.getElementById('editShiftModal'));

      // (รวม Options สำหรับ Admin)
      Object.assign(calendarOptions, {
        initialView: 'resourceTimelineWeek', // (มุมมองบอร์ด)
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'resourceTimelineWeek,resourceTimelineDay' // (ปุ่มเปลี่ยน View)
        },

        // (สำคัญ) API สำหรับดึง "พนักงาน" (Resources)
        // API นี้ควรรู้ว่าต้องส่งพนักงาน "ทุกคน" กลับมา
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
        },

        // (Event: คลิกช่องว่าง)
        dateClick: function (info) {
          document.getElementById('editShiftEmployeeName').textContent = info.resource.title;
          document.getElementById('editShiftDate').textContent = info.dateStr;
          document.getElementById('editShiftUserId').value = info.resource.id;
          document.getElementById('editShiftDateInput').value = info.dateStr;
          document.getElementById('schedule_id').value = '0';
          document.getElementById('editShiftResult').innerHTML = '';
          editModal.show();
        },

        // (Event: คลิกกะที่มีอยู่)
        eventClick: function (info) {
          let props = info.event.extendedProps;
          document.getElementById('editShiftEmployeeName').textContent = info.event.getResources()[0].title;
          document.getElementById('editShiftDate').textContent = info.event.startStr;
          document.getElementById('editShiftUserId').value = info.event.getResources()[0].id;
          document.getElementById('editShiftDateInput').value = info.event.startStr;
          document.getElementById('schedule_id').value = props.schedule_id;
          document.getElementById('editShiftResult').innerHTML = '';
          editModal.show();
        }
      });

    } else {

      // --- EMPLOYEE OPTIONS ---

      // (รวม Options สำหรับ Employee)
      Object.assign(calendarOptions, {
        initialView: 'timeGridWeek', // (มุมมองปฏิทินสัปดาห์)
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'timeGridWeek,timeGridDay,dayGridMonth' // (ปุ่มเปลี่ยน View)
        }
        // (Employee ไม่ต้องมี resources, dateClick, eventClick (modal))
      });
    }

    // *** (จุดที่ 3) สร้างปฏิทินด้วย Options ที่เลือกไว้ ***
    var calendar = new FullCalendar.Calendar(calendarEl, calendarOptions);
    calendar.render();

    // *** (จุดที่ 4) โค้ดสำหรับรับค่า Modal (จะทำงานเฉพาะ Admin) ***
    var editShiftForm = document.getElementById('editShiftForm');
    if (editShiftForm) {
      editShiftForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var formData = new FormData(editShiftForm);

        document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-info">กำลังบันทึก...</div>';

        fetch('../api/api_update_shift.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-success">บันทึกสำเร็จ!</div>';
              setTimeout(function () {
                editModal.hide();
                calendar.refetchEvents(); // (สำคัญ) โหลดกะใหม่
              }, 500);
            } else {
              document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-danger">' + (data.message || 'เกิดข้อผิดพลาด') + '</div>';
            }
          })
          .catch(error => {
            document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</div>';
          });
      });
    }
  });
</script>

<?php
$conn->close();
require_once 'footer.php'; // (ใช้ footer.php ที่อยู่ใน includes/)
?>