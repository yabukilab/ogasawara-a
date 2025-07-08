window.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('timetable');
  const subjectList = document.getElementById('subjectList');
  let selectedSubjectId = null;

  // 科目クリックで選択
  subjectList.addEventListener('click', (e) => {
    if (e.target.tagName === 'LI') {
      selectedSubjectId = e.target.dataset.id;

      // ハイライト
      subjectList.querySelectorAll('li').forEach(li => li.classList.remove('active'));
      e.target.classList.add('active');
    }
  });

  // セルクリックで科目をセット（2コマ分）
  table.addEventListener('click', (e) => {
    // 削除ボタンが押された場合の処理
    if (e.target.classList.contains('remove-btn')) {
      const td = e.target.closest('td');
      const day = parseInt(td.dataset.day);
      const period = parseInt(td.dataset.period);

      // 2時限分のセルを削除する処理
      for (let offset = 0; offset < 2; offset++) {
        const targetPeriod = period + offset;
        const targetCell = document.querySelector(`td[data-day="${day}"][data-period="${targetPeriod}"]`);
        if (targetCell) {
          targetCell.innerHTML = '';
          delete targetCell.dataset.subjectId;
          targetCell.classList.remove('selected');
        }
      }
      e.stopPropagation();
      return;
    }

    // セルがクリックされたら科目をセット
    if (e.target.tagName === 'TD') {
      const cell = e.target;
      const day = parseInt(cell.dataset.day);
      const period = parseInt(cell.dataset.period);

      if (selectedSubjectId) {
        for (let offset = 0; offset < 2; offset++) {
          const targetPeriod = period + offset;
          const targetCell = document.querySelector(`td[data-day="${day}"][data-period="${targetPeriod}"]`);
          if (targetCell) {
            const subject = subjects.find(s => s.id == selectedSubjectId);
            if (subject) {
              targetCell.innerHTML = `
                <div class="td-content">
                  ${subject.name}
                  <button class="remove-btn" title="削除">×</button>
                </div>
              `;
              targetCell.dataset.subjectId = subject.id;
              targetCell.classList.add('selected');
            }
          }
        }
      }
    }
  });

  // フォーム送信時：全マスの状態を収集（subject_id が null のセルも送る）
  const form = document.getElementById('saveForm');
  form.addEventListener('submit', (e) => {
    const data = [];
    const cells = table.querySelectorAll('td');
    cells.forEach(cell => {
      const day = parseInt(cell.dataset.day);
      const period = parseInt(cell.dataset.period);
      const subjectId = cell.dataset.subjectId ?? null;

      data.push({
        day,
        period,
        subject_id: subjectId
      });
    });
    document.getElementById('timetableData').value = JSON.stringify(data);
  });

  // 保存済みのデータ（PHPから渡された変数）を描画
  if (Array.isArray(registeredTimetable)) {
    registeredTimetable.forEach(entry => {
      const day = parseInt(entry.day);
      const period = parseInt(entry.period);
      const cell = document.querySelector(`td[data-day="${day}"][data-period="${period}"]`);
      if (cell) {
        const subject = subjects.find(s => s.id == entry.subject_id);
        if (subject) {
          cell.innerHTML = `
            <div class="td-content">
              ${subject.name}
              <button class="remove-btn" title="削除">×</button>
            </div>
          `;
          cell.dataset.subjectId = entry.subject_id;
          cell.classList.add('selected');
        }
      }
    });
  }
});
