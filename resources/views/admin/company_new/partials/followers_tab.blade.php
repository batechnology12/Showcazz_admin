{{-- resources/views/admin/company/partials/followers_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped table-bordered table-hover" id="followersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>User Type</th>
                  
                    <th>Followed Since</th>
                    
                </tr>
            </thead>
            <tbody>
                @foreach($followers as $follower)
                <tr>
                    <td>{{ $follower->user->id ?? 'N/A' }}</td>
                    <td>
                        @if($follower->user)
                            @if($follower->user->image)
                                <img src="{{ (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $follower->user->image) : asset('user_images/' . $follower->user->image)) }}" 
                                     alt="{{ $follower->user->getName() }}" 
                                     style="max-width: 40px; max-height: 40px; border-radius: 50%;" 
                                     class="img-thumbnail">
                            @endif
                            <strong>{{ $follower->user->getName() }}</strong>
                        @else
                            <span class="label label-danger">User Deleted</span>
                        @endif
                    </td>
                    <td>{{ $follower->user->email ?? 'N/A' }}</td>
                    <td>
                        @if($follower->user)
                            <span class="label label-info">{{ ucfirst($follower->user->usertype ?? 'User') }}</span>
                        @endif
                    </td>
                   
                    <td>{{ $follower->created_at->format('d M Y h:i A') }}</td>
                   
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $followers->links() }}
            </div>
        </div>
    </div>
</div>