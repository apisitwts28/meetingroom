document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const roomCheckboxes = document.querySelectorAll('.room-filter');

  // ฟังก์ชันดึงห้องที่ถูกติ๊กเลือก
  function getSelectedRooms() {
    return Array.from(roomCheckboxes)
      .filter(cb => cb.checked)
      .map(cb => cb.value);
  }

  // สร้างปฏิทิน
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'th',
    height: 'auto',
    contentHeight: 600,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    buttonText: {
      today: 'วันนี้',
      month: 'เดือน',
      week: 'สัปดาห์',
      list: 'รายการ'
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },
    // ดึงข้อมูลจาก API
    events: function (info, successCallback, failureCallback) {
      const roomIds = getSelectedRooms().join(',');
      fetch(`reservation_feed.php?start=${info.startStr}&end=${info.endStr}&rooms=${roomIds}`)
        .then(res => res.json())
        .then(data => {
          successCallback(data.map(ev => ({
            id: ev.id,
            title: ev.title,
            start: ev.start,
            end: ev.end,
            color: ev.color,
            extendedProps: {
              description: ev.description,
              room: ev.room,
              user: ev.user,
              attendees: ev.attendees
            }
          })));
        })
        .catch(err => {
          console.error("Error fetching events:", err);
          failureCallback(err);
        });
    },
    // เมื่อคลิกที่รายการจอง
    eventClick: function (info) {
      const ev = info.event;
      const props = ev.extendedProps;
      
      // แปลงวันที่ให้สวยงาม
      const start = new Date(ev.start);
      const end = new Date(ev.end);
      const options = { day: 'numeric', month: 'long', year: 'numeric' };
      const dateStr = start.toLocaleDateString('th-TH', options);
      const timeStr = `${start.toLocaleTimeString('th-TH', {hour:'2-digit', minute:'2-digit'})} - ${end.toLocaleTimeString('th-TH', {hour:'2-digit', minute:'2-digit'})}`;

      // ยัดข้อมูลใส่ Modal
      document.getElementById('modalTitle').textContent = ev.title || '-';
      document.getElementById('modalRoom').textContent = props.room || '-';
      document.getElementById('modalTime').textContent = `${dateStr} เวลา ${timeStr}`;
      document.getElementById('modalUser').textContent = props.user || '-';
      
      // จัดการ Comment/Description
      const desc = props.description;
      const descEl = document.getElementById('modalDesc');
      if (desc && desc !== 'null' && desc !== '') {
          descEl.textContent = desc;
          descEl.parentElement.classList.remove('hidden'); // แสดงกล่อง
      } else {
          descEl.textContent = '-';
          // descEl.parentElement.classList.add('hidden'); // ซ่อนกล่องถ้าไม่มี (ถ้าต้องการ)
      }

      // เปิด Modal (เรียกฟังก์ชัน global ที่อยู่ใน index.php)
      if (typeof openModal === 'function') {
          openModal();
      } else {
          // Fallback กรณีไม่มี animation function
          document.getElementById('eventModal').classList.remove('hidden');
      }
    }
  });

  calendar.render();

  // เมื่อติ๊กเลือกห้อง ให้โหลดปฏิทินใหม่
  roomCheckboxes.forEach(cb => {
    cb.addEventListener('change', () => calendar.refetchEvents());
  });
});