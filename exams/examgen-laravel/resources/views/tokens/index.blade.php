@extends('layouts.app')
@section('title','Tokens & Transactions')

@section('content')
  <div class="grid-2">
    <div class="card">
      <h3 style="margin:0 0 10px">Current Balance</h3>
      <div style="font-size:36px;font-weight:700">120</div>
      <div class="muted">tokens</div>
      <div style="margin-top:12px">
        <button class="btn primary">Charge</button>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 10px">Recent Transactions</h3>
      <table class="table">
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Reason</th></tr></thead>
        <tbody>
          <tr><td>2025-05-23</td><td>consume</td><td>-5</td><td>Publish exam</td></tr>
          <tr><td>2025-04-20</td><td>charge</td><td>+50</td><td>Admin top-up</td></tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection
