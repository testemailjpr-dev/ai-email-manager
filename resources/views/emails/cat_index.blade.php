@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 100%; border-radius: 1rem;">
        <div class="card-body">
            <h2>Gmail Messages for Category: <strong>{{ $category->name }}</strong></h2>
            <form id="bulkActionForm" method="POST" action="{{ route('emails.bulkAction') }}"> @csrf
           
                <div style="text-align:right;">
                    <button type="button" name="action1" value="delete" class="btn btn-danger btn-sm" onclick="checkCheckBoxes('delete');">
                        <i class="bi bi-trash"></i> Move to Trash
                    </button>
                    <button type="button" name="action2" value="archive" class="btn btn-secondary btn-sm me-2" onclick="checkCheckBoxes('unsubscribe');">
                        <i class="bi bi-box-arrow-right"></i> Unsubscribe
                    </button>
                    <input type="hidden" name="action" id="action" value="" />
                </div>
            <div class="list-group mt-3">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><div class="form-check"><input class="form-check-input" type="checkbox" id="selectAll"></div></th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>AI Summary</th>
                            <th>Date</th>
                            @if($showAccount)
                            <th>Gmail Account</th>
				            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($emails as $msg)
                            <tr>
                            	<td><input type="checkbox" name="email_ids[]" value="{{ $msg->gmail_id }}" class="email-checkbox"></td>
                                <td>{{ $msg['from'] }}</td>
                                <td><a href="{{ route('emails.show', $msg['gmail_id']) }}">{{ $msg['subject'] }}</a></td>
                                <td>{{ $msg['summary'] ?? '(processing...)' }}</td>
                                <td>{{ $msg->created_at->format('Y-m-d H:i') }}</td>
                                @if($showAccount)
                                <td>{{ $msg->account_email }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">
                                <div class="d-flex justify-content-center bt-pagination">
                                    {{ $emails->links() }}
                                </div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.email-checkbox');
    checkboxes.forEach(cb => cb.checked = e.target.checked);
});
function checkCheckBoxes(action){
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
	if( checkboxes.length == 0 ){
		alert('Please select at least one Email.');
		return;
	}
	document.getElementById('action').value = action;
	document.getElementById('bulkActionForm').submit();
}
</script>
@endsection
