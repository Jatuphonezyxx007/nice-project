<?php
// schedule_calendar.php
require_once '../config.php';
// (*** แก้ไข ***) header.php อยู่ในโฟลเดอร์เดียวกัน (includes)
require_once 'header.php';
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/locales/th.js'></script>
<title>ปฏิทินกะการทำงาน</title>

<div class="container">
  <h3><i class="bi bi-calendar3 me-2"></i> ปฏิทินกะการทำงาน</h3>
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
            <h5 class="modal-title" id="editShiftModalLabel">แก้ไขกะการทำงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>พนักงาน: <strong id="editShiftEmployeeName"></strong></p>
            <p>สัปดาห์: <strong id="editShiftWeekDate"></strong></p>

            <input type="hidden" name="user_id" id="editShiftUserId">
            <input type="hidden" name="start_date" id="editShiftStartDate">
            <input type="hidden" name="end_date" id="editShiftEndDate">

            <div class="mb-3">
              <label for="schedule_id" class="form-label">เลือกกะใหม่:</label>
              <select class="form-control" name="schedule_id" id="schedule_id" required>
                <option value="0">-- ไม่กำหนดกะ (หยุด/ลา) --</option>
                <?php
                // (ดึงกะมาใส่ใน Dropdown เหมือนหน้า manage_shifts.php)
                $schedules_result = $conn->query("SELECT * FROM schedule_types ORDER BY id");
                while ($schedule = $schedules_result->fetch_assoc()):
                  ?>
                  <option value="<?php echo $schedule['id']; ?>">
                    <?php echo htmlspecialchars($schedule['name'] . ' (' . $schedule['time_in'] . ' - ' . $schedule['time_out'] . ')'); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div id="editShiftResult" class="mt-3"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    // (*** แก้ไข ***) ตรวจสอบสิทธิ์ก่อนเรียก Modal เพื่อป้องกัน Error
    var editModal = null;
    if ('<?php echo $current_user_role; ?>' === 'admin') {
      editModal = new bootstrap.Modal(document.getElementById('editShiftModal'));
    }

    // 4. (*** นี่คือหัวใจหลัก ***) สร้างปฏิทิน
    var calendar = new FullCalendar.Calendar(calendarEl, {

      initialView: 'dayGridMonth', // มุมมองรายเดือน
      locale: 'th', // ภาษาไทย

      // (ปุ่ม Header)
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek' // (เพิ่มปุ่มดูรายสัปดาห์)
      },

      // (*** แก้ไข ***) บอก FullCalendar ให้ "ถอยหลัง 1 ขั้น" แล้ว "เข้าไปที่ api"
      events: '../api/api_get_schedules.php',

      // (*** สำคัญ ***) ตรรกะการแก้ไขกะสำหรับ Admin

      // 5. ทำให้แก้ไขได้ (เฉพาะ Admin)
      editable: <?php echo ($current_user_role == 'admin') ? 'true' : 'false'; ?>,

      // 6. เมื่อ "คลิก" ที่กะ (สำหรับ Admin)
      eventClick: function (info) {

        // ถ้าไม่ใช่ Admin ให้ออกจากฟังก์ชัน
        if ('<?php echo $current_user_role; ?>' !== 'admin') {
          return;
        }

        // ดึงข้อมูลที่ซ่อนไว้ (extendedProps)
        let props = info.event.extendedProps;
        let title = info.event.title;
        let weekStart = new Date(props.schedule_start);
        let weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6); // +6 วัน

        // 7. ตั้งค่า Modal
        document.getElementById('editShiftEmployeeName').textContent = title.split(':')[0]; // "สมชาย"
        document.getElementById('editShiftWeekDate').textContent = props.schedule_start + ' ถึง ' + weekEnd.toISOString().split('T')[0];
        document.getElementById('editShiftUserId').value = props.user_id;
        document.getElementById('editShiftStartDate').value = props.schedule_start;
        document.getElementById('editShiftEndDate').value = weekEnd.toISOString().split('T')[0];

        // (ต้อง Query หากะเดิมของคนนี้มาใส่ใน dropdown ด้วย... 
        // แต่เพื่อความง่าย กดแล้วให้ Admin เลือกใหม่เลย)
        document.getElementById('schedule_id').value = '0';
        document.getElementById('editShiftResult').innerHTML = '';

        // 8. เปิด Modal
        editModal.show();
      },

      // 9. เมื่อ "ลาก" กะ (สำหรับ Admin) - (ส่วนนี้จะอัปเดตข้อมูลเมื่อลาก)
      eventDrop: function (info) {
        if (!confirm("คุณแน่ใจหรือไม่ว่าต้องการย้ายกะนี้?")) {
          info.revert(); // (ย้ายกลับที่เดิมถ้ากด Cancel)
          return;
        }

        // ส่งข้อมูลไปอัปเดต (AJAX) - (ส่วนนี้คุณต้องสร้าง API สำหรับอัปเดตคล้ายๆ กับ Form)
        console.log('ย้ายกะของ user ' + info.event.extendedProps.user_id);
        console.log('ไปเริ่มวันที่ ' + info.event.start.toISOString().split('T')[0]);

        // (คุณต้องสร้าง api_update_schedule_date.php มาจัดการส่วนนี้)
        /*
        fetch('../api/api_update_schedule_date.php', { // (*** แก้ไข Path ***)
            method: 'POST',
            body: JSON.stringify({
                user_id: info.event.extendedProps.user_id,
                old_start_date: info.event.extendedProps.schedule_start,
                new_start_date: info.event.start.toISOString().split('T')[0]
            })
        }).then(res => res.json()).then(data => {
            if(data.success) {
                alert('ย้ายสำเร็จ!');
                calendar.refetchEvents(); // โหลดข้อมูลใหม่
            } else {
                alert('ย้ายล้มเหลว!');
                info.revert();
            }
        });
        */
      }
    });

    // 10. สั่งให้ปฏิทินแสดงผล
    calendar.render();


    // 11. (*** สำคัญ ***) จัดการ Form Modal (AJAX)
    // (ใช้ Logic คล้ายกับ manage_shifts.php แต่ยิง AJAX)
    var editShiftForm = document.getElementById('editShiftForm');
    if (editShiftForm) {
      editShiftForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var formData = new FormData(editShiftForm);

        // เราต้องสร้าง API สำหรับการแก้ไขนี้
        // (คุณสามารถสร้าง api_update_shift.php โดยใช้ Logic เดียวกับ manage_shifts.php ได้เลย)

        fetch('../api/api_update_shift.php', { // (*** แก้ไข Path ***) (*** คุณต้องสร้างไฟล์นี้ใน api/ ***)
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById('editShiftResult').innerHTML = '<div class="alert alert-success">เปลี่ยนกะสำเร็จ!</div>';
              setTimeout(function () {
                editModal.hide();
                calendar.refetchEvents(); // (*** สำคัญ ***) สั่งให้ปฏิทินโหลดข้อมูลใหม่
              }, 1000);
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
require_once 'footer.php'; // (*** แก้ไข ***) footer.php อยู่ในโฟลเดอร์เดียวกัน (includes)
?>