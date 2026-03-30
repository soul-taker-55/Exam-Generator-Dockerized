{{-- FRAGMENT ONLY: Questions (no layout) --}}
@php
  $subjectId = request('subject_id');
  $rows = $questions ?? [
    ['id'=>1,'text'=>'Example: What is SQL Injection?','difficulty'=>1,'points'=>5,'date'=>'2025-05-15'],
    ['id'=>2,'text'=>'Example: Explain XSS.','difficulty'=>2,'points'=>10,'date'=>'2025-05-20'],
    ['id'=>3,'text'=>'Example: Dijkstra complexity.','difficulty'=>3,'points'=>15,'date'=>'2025-05-25'],
  ];
  $diff = fn($d)=> $d==1?'Easy':($d==2?'Medium':'Hard');
  $q = array_filter(['embed'=>1,'subject_id'=>$subjectId]);
@endphp

<div class="qsph">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px">
    <div style="display:flex;gap:8px;align-items:center;">
      <input type="text" placeholder="Search..." style="padding:8px 10px;border:1px solid #ddd;border-radius:6px;min-width:220px">
      <select style="padding:8px 10px;border:1px solid #ddd;border-radius:6px">
        <option>All difficulties</option><option>Easy</option><option>Medium</option><option>Hard</option>
      </select>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('questions.import',$q) }}" class="btn" style="text-decoration:none;padding:8px 12px;border-radius:6px;background:#95a5a6;color:#fff">
        <i class="fa-solid fa-file-import"></i> Import
      </a>
      <a href="{{ route('questions.create',$q) }}" class="btn" style="text-decoration:none;padding:8px 12px;border-radius:6px;background:#3498db;color:#fff">
        <i class="fa-solid fa-plus"></i> Add Question
      </a>
    </div>
  </div>

  <div style="border:1px solid #eee;border-radius:8px;overflow:auto">
    <table style="width:100%;border-collapse:separate;border-spacing:0">
      <thead>
        <tr style="background:#f8f9fa">
          <th style="text-align:left;padding:10px 12px;border-bottom:1px solid #eee">#</th>
          <th style="text-align:left;padding:10px 12px;border-bottom:1px solid #eee">Text</th>
          <th style="text-align:left;padding:10px 12px;border-bottom:1px solid #eee">Difficulty</th>
          <th style="text-align:left;padding:10px 12px;border-bottom:1px solid #eee">Points</th>
          <th style="text-align:left;padding:10px 12px;border-bottom:1px solid #eee">Date</th>
          <th style="text-align:right;padding:10px 12px;border-bottom:1px solid #eee">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        @php
          $id    = $r['id'] ?? $r->id;
          $text  = $r['text'] ?? ($r->text ?? '');
          $d     = (int)($r['difficulty'] ?? ($r->difficulty ?? 1));
          $pts   = (int)($r['points'] ?? ($r->points ?? 1));
          $date  = \Carbon\Carbon::parse($r['date'] ?? ($r->date ?? now()))->format('d M Y');
          $badge = $d===1?'#27ae60':($d===2?'#f39c12':'#e74c3c');
        @endphp
        <tr>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2">{{ $id }}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2">{{ $text }}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2">
            <span style="display:inline-block;padding:4px 8px;border-radius:6px;background:{{ $badge }};color:#fff">{{ $diff($d) }}</span>
          </td>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2">{{ $pts }}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2">{{ $date }}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #f2f2f2;text-align:right">
            <a href="{{ route('questions.edit',['id'=>$id] + $q) }}"
               style="padding:6px 10px;border-radius:6px;background:#3498db;color:#fff;text-decoration:none">
              <i class="fa-solid fa-pen-to-square"></i> Edit
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="padding:16px;color:#7f8c8d;text-align:center">No questions.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
