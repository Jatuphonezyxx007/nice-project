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

  /* สไตล์ของ "แถว" พนักงาน (Resource) */
  .fc-resource-timeline-lane {
    border-bottom: 1px solid #eee !important;
  }

  /* สไตล์ของ "กะ" (Event) */
  .fc-event {
    border: none !important;
    padding: 4px 8px !important;
    font-weight: 500;
    cursor: pointer;
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
  <?php if ($current_user_role == 'admin'): ?>
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
</style>

<div class="container">
  <h3><i class="bi bi-calendar3 me-2"></i> บอร์ดจัดการกะ (Shift Board)</h3>
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
    var calendarEl = document.getElementById('calendar');

    // (Modal (สำหรับ Admin))
    var editModal = null;
    if ('<?php echo $current_user_role; ?>' === 'admin') {
      editModal = new bootstrap.Modal(document.getElementById('editShiftModal'));
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
      // (สำคัญ) เปลี่ยน View
      initialView: 'resourceTimelineWeek',
      locale: 'th',

      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'resourceTimelineWeek,resourceTimelineDay' // เปลี่ยนเป็น View ที่ต้องการ
      },

      // (สำคัญ) บอกว่า "แถว" (Resources) มาจากไหน
      resources: '../api/api_get_employees.php',

      // (สำคัญ) บอกว่า "กะ" (Events) มาจากไหน
      events: '../api/api_get_schedules.php',

      // (ใหม่) ทำให้แถวพนักงานแสดงรูปภาพ
      resourceAreaHeaderContent: 'พนักงาน',
      resourceLabelContent: function (arg) {
        let imgHtml = '';
        if (arg.resource.extendedProps.image_url) {
          imgHtml = `<img src="${arg.resource.extendedProps.image_url}" alt="">`;
        } else {
          // (ใส่ไอคอนแทนถ้ารูปไม่มี)
          imgHtml = '<i class="bi bi-person-circle" style="font-size: 32px; margin-right: 10px; color: #ccc;"></i>';
        }
        return { html: imgHtml + arg.resource.title };
      },

      // (ใหม่) ทำให้ Event (กะ) แสดงเวลา
      eventContent: function (arg) {
        let time = arg.event.extendedProps.time_range || '';
        return {
          html: `<b>${arg.event.title}</b><br><small>${time}</small>`
        };
      },

      // (ใหม่) ถ้า Admin "คลิกที่ช่องว่าง"
      dateClick: function (info) {
        if ('<?php echo $current_user_role; ?>' !== 'admin') {
          return; // Employee คลิกไม่ได้
        }

        // info.resource.id คือ ID พนักงาน (จากแถว)
        // info.dateStr คือ วันที่ (YYYY-MM-DD)

        document.getElementById('editShiftEmployeeName').textContent = info.resource.title;
        document.getElementById('editShiftDate').textContent = info.dateStr;
        document.getElementById('editShiftUserId').value = info.resource.id;
        document.getElementById('editShiftDateInput').value = info.dateStr;

        // ตั้งค่าเริ่มต้น (ลบกะ)
        document.getElementById('schedule_id').value = '0';
        document.getElementById('editShiftResult').innerHTML = '';
        editModal.show();
      },

      // (ใหม่) ถ้า Admin "คลิกที่กะที่มีอยู่"
      eventClick: function (info) {
        if ('<?php echo $current_user_role; ?>' !== 'admin') {
          return; // Employee คลิกไม่ได้
        }

        let props = info.event.extendedProps;

        document.getElementById('editShiftEmployeeName').textContent = info.event.getResources()[0].title;
        document.getElementById('editShiftDate').textContent = info.event.startStr;
        document.getElementById('editShiftUserId').value = info.event.getResources()[0].id;
        document.getElementById('editShiftDateInput').value = info.event.startStr;

        // (สำคัญ) ตั้งค่า Dropdown ให้เป็นกะที่เลือกอยู่
        document.getElementById('schedule_id').value = props.schedule_id;
        document.getElementById('editShiftResult').innerHTML = '';
        editModal.show();
      },

      // (ปิดการลาก) การลากจะซับซ้อนกับตรรกะรายวัน
      editable: false,

    });

    calendar.render();

    // (อัปเดต) จัดการ Form Modal (AJAX) (ชี้ไปที่ api_update_shift.php ใหม่)
    var editShiftForm = document.getElementById('editShiftForm');
    if (editShiftForm) {
      editShiftForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var formData = new FormData(editShiftForm);

        document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-info">กำลังบันทึก...</div>';

        fetch('../api/api_update_shift.php', { // (ใช้ API ใหม่)
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