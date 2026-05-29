@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('admin.notifications.dashboard') }}">Notification Dashboard</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Advanced Notification</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Send Advanced Push Notification</h3>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-body">
                        <form id="notificationForm" method="POST" action="{{ route('admin.notifications.advanced.send') }}">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-pencil"></i>
                                                <span class="caption-subject font-blue-hoki bold uppercase">Content</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="form-group @error('title') has-error @enderror">
                                                <label class="control-label">Notification Title <span class="required">*</span></label>
                                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Enter catchy title" required>
                                                @error('title')
                                                    <span class="help-block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group @error('message') has-error @enderror">
                                                <label class="control-label">Notification Message <span class="required">*</span></label>
                                                <textarea name="message" class="form-control" rows="4" placeholder="Enter notification message" required>{{ old('message') }}</textarea>
                                                @error('message')
                                                    <span class="help-block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group @error('image') has-error @enderror">
                                                <label class="control-label">Rich Media Image URL (Optional)</label>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-image"></i>
                                                    </span>
                                                    <input type="url" name="image" class="form-control" value="{{ old('image') }}" placeholder="https://example.com/banner.jpg">
                                                </div>
                                                <span class="help-block">Recommended size: 1024x512 pixels.</span>
                                                @error('image')
                                                    <span class="help-block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-link"></i>
                                                <span class="caption-subject font-green-hoki bold uppercase">Action & Interactivity</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Click Action (Screen)</label>
                                                        <select name="action_type" id="actionType" class="form-control">
                                                            <option value="open_app">Home Screen (Default)</option>
                                                            <option value="post_detail">Post Details</option>
                                                            <option value="profile">User Profile</option>
                                                            <option value="chat">Chat Screen</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group" id="actionValueSection" style="display: none;">
                                                        <label class="control-label">Target ID / Link</label>
                                                        <div class="input-group">
                                                            <input type="text" name="action_value" id="actionValue" class="form-control" placeholder="Search or enter ID">
                                                            <span class="input-group-addon">
                                                                <i class="fa fa-search"></i>
                                                            </span>
                                                        </div>
                                                        <div id="suggestionBox" class="suggestion-container" style="display: none;">
                                                            <!-- Suggestions will appear here -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="help-block" id="actionHelpText">Users will land on the home screen when they tap the notification.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-mobile"></i>
                                                <span class="caption-subject font-red-hoki bold uppercase">Device Preview</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="device-preview">
                                                <div class="notification-bubble">
                                                    <div class="app-info">
                                                        <img src="{{ asset('admin_assets/layouts/layout/img/logo-invert.png') }}" alt="App Logo">
                                                        <span>Showcazz • Just now</span>
                                                    </div>
                                                    <div class="notification-content">
                                                        <h4 id="previewTitle">Notification Title</h4>
                                                        <p id="previewMessage">Notification message will appear here...</p>
                                                        <div id="previewImageContainer" style="display: none;">
                                                            <img id="previewImage" src="" class="img-responsive">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-bullseye"></i>
                                                <span class="caption-subject font-purple-hoki bold uppercase">Targeting</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="form-group">
                                                <label>Target Audience</label>
                                                <select name="target_type" id="targetType" class="form-control" required>
                                                    <option value="all">All Users ({{ $counts['all'] }})</option>
                                                    <option value="companies">All Companies ({{ $counts['companies'] }})</option>
                                                    <option value="students">All Students ({{ $counts['students'] }})</option>
                                                    <option value="professionals">All Professionals ({{ $counts['professionals'] }})</option>
                                                    <option value="subscribed">Subscribed Only ({{ $counts['subscribed'] }})</option>
                                                    <option value="custom">Custom Selection</option>
                                                </select>
                                            </div>

                                            <div id="customUserSection" style="display: none;">
                                                <button type="button" class="btn btn-block btn-outline blue" id="openUserSelector">
                                                    <i class="fa fa-users"></i> Select Users (<span id="selectedCount">0</span>)
                                                </button>
                                                <div id="selectedUserPreview" style="margin-top: 10px; max-height: 100px; overflow-y: auto;">
                                                    <!-- Small tags for selected users -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions right">
                                <button type="submit" class="btn btn-lg btn-success" id="submitBtn">
                                    <i class="fa fa-paper-plane"></i> Launch Campaign
                                </button>
                                <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-lg btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- User Selector Modal --}}
<div class="modal fade" id="userSelectorModal" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">Select Target Users</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <div class="input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="userSearch" class="form-control" placeholder="Type name, email or company to find users...">
                            </div>
                        </div>
                        <div id="searchResults" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px;">
                            <div class="text-center p-20" style="padding: 40px; color: #999;">
                                <i class="fa fa-users fa-3x"></i>
                                <p>Start typing to search users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="portlet light bordered" style="background: #fbfbfb;">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="caption-subject bold uppercase">Selected (<span id="modalSelectedCount">0</span>)</span>
                                </div>
                                <div class="actions">
                                    <a href="javascript:;" id="clearAllSelected" class="btn btn-xs red">Clear</a>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div id="selectedUsersList" style="max-height: 350px; overflow-y: auto;">
                                    <!-- Selected list -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
                <button type="button" class="btn green" data-dismiss="modal">Confirm Selection</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .device-preview {
        background: #000;
        padding: 40px 10px;
        border-radius: 30px;
        position: relative;
        max-width: 280px;
        margin: 0 auto;
    }
    .notification-bubble {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    .app-info {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    .app-info img {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        margin-right: 8px;
    }
    .app-info span {
        font-size: 11px;
        color: #666;
    }
    .notification-content h4 {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #000;
    }
    .notification-content p {
        margin: 0;
        font-size: 13px;
        color: #333;
        line-height: 1.4;
    }
    #previewImageContainer {
        margin-top: 8px;
        border-radius: 8px;
        overflow: hidden;
    }
    
    /* Suggestions */
    .suggestion-container {
        position: absolute;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        z-index: 1000;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 0 0 4px 4px;
    }
    .suggestion-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f5f5f5;
        cursor: pointer;
    }
    .suggestion-item:hover { background: #f0f7ff; }
    .suggestion-item .title { font-weight: 600; font-size: 13px; display: block; }
    .suggestion-item .meta { font-size: 11px; color: #888; }
    
    /* User Selector */
    .user-item {
        padding: 12px;
        border-bottom: 1px solid #f5f5f5;
        cursor: pointer;
        transition: all 0.2s;
    }
    .user-item:hover { background: #f9f9f9; }
    .user-item.selected { background: #e3f2fd; border-left: 4px solid #2196f3; }
    
    .selected-tag {
        display: inline-block;
        background: #f1f1f1;
        padding: 4px 10px;
        border-radius: 15px;
        margin: 2px;
        font-size: 11px;
        color: #555;
    }
    
    .selected-user-row {
        padding: 8px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 4px;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .selected-user-row .name { font-size: 12px; font-weight: 600; }
    .selected-user-row .remove { color: #e7505a; cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    let selectedUsers = new Set();
    let searchTimeout;

    // Preview Real-time Update
    $('input[name="title"]').on('input change keyup', function() {
        $('#previewTitle').text($(this).val() || 'Notification Title');
    }).trigger('input');

    $('textarea[name="message"]').on('input change keyup', function() {
        $('#previewMessage').text($(this).val() || 'Notification message will appear here...');
    }).trigger('input');

    $('input[name="image"]').on('input change keyup', function() {
        let url = $(this).val();
        if (url) {
            $('#previewImage').attr('src', url);
            $('#previewImageContainer').show();
        } else {
            $('#previewImageContainer').hide();
        }
    }).trigger('input');

    // Action Type & Suggestions Logic
    $('#actionType').change(function() {
        let type = $(this).val();
        let help = '';
        $('#actionValue').val('');
        $('#suggestionBox').hide();
        
        switch(type) {
            case 'open_app': help = 'Users will land on the home screen.'; break;
            case 'post_detail': help = 'Search and select a Post. Users will land on that post\'s detail page.'; break;
            case 'profile': help = 'Search and select a User. Users will land on that user\'s profile.'; break;
            case 'chat': help = 'Search and select a User. Users will land on the chat screen with them.'; break;
        }
        $('#actionHelpText').text(help);

        // Toggle visibility of the target ID field - ONLY for post_detail
        if (type === 'post_detail') {
            $('#actionValueSection').fadeIn();
        } else {
            $('#actionValueSection').fadeOut();
        }
    });

    $('#actionValue').on('input', function() {
        let q = $(this).val();
        let type = $('#actionType').val();
        
        if (q.length < 2 || !['post_detail', 'profile', 'chat'].includes(type)) {
            $('#suggestionBox').hide();
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            let url = type === 'post_detail' ? '{{ route("admin.notifications.posts.search") }}' : '{{ route("admin.notifications.users.search") }}';
            $.get(url, { q: q }, function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        html += `
                            <div class="suggestion-item" data-id="${item.id}" data-title="${item.title || item.name}">
                                <span class="title">${item.title || item.name}</span>
                                <span class="meta">ID: ${item.id} • ${item.date || item.usertype || ''}</span>
                            </div>
                        `;
                    });
                    $('#suggestionBox').html(html).show();
                } else {
                    $('#suggestionBox').hide();
                }
            });
        }, 300);
    });

    $(document).on('click', '.suggestion-item', function() {
        let id = $(this).data('id');
        let title = $(this).data('title');
        $('#actionValue').val(id);
        $('#suggestionBox').hide();
        toastr.info('Target set to: ' + title);
    });

    // Targeting Logic
    $('#targetType').change(function() {
        if ($(this).val() === 'custom') {
            $('#customUserSection').fadeIn();
        } else {
            $('#customUserSection').fadeOut();
        }
    });

    // User Selection Logic
    $('#openUserSelector').click(function() {
        $('#userSelectorModal').modal('show');
    });

    $('#userSearch').on('input', function() {
        let q = $(this).val();
        if (q.length < 2) return;

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#searchResults').html('<div class="text-center" style="padding:40px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
            $.get('{{ route("admin.notifications.users.search") }}', { q: q }, function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(user => {
                        let isSelected = selectedUsers.has(user.id) ? 'selected' : '';
                        html += `
                            <div class="user-item ${isSelected}" data-id="${user.id}" data-name="${user.name}" data-email="${user.email}">
                                <div class="row">
                                    <div class="col-xs-10">
                                        <div style="font-weight:600;">${user.name}</div>
                                        <div style="font-size:11px; color:#777;">${user.email} • ${user.usertype}</div>
                                    </div>
                                    <div class="col-xs-2 text-right">
                                        <i class="fa ${isSelected ? 'fa-check-circle text-success' : 'fa-circle-o'}" style="font-size:18px;"></i>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    if (html === '') html = '<div class="text-center" style="padding:40px;">No users found</div>';
                    $('#searchResults').html(html);
                }
            });
        }, 300);
    });

    $(document).on('click', '.user-item', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        if (selectedUsers.has(id)) {
            removeUser(id);
        } else {
            addUser(id, name);
        }
        updateUI();
    });

    function addUser(id, name) {
        selectedUsers.add(id);
        $(`.user-item[data-id="${id}"]`).addClass('selected').find('i').removeClass('fa-circle-o').addClass('fa-check-circle text-success');
        $('#selectedUsersList').prepend(`
            <div class="selected-user-row" data-id="${id}">
                <span class="name">${name}</span>
                <i class="fa fa-trash remove" onclick="removeUser(${id})"></i>
            </div>
        `);
    }

    window.removeUser = function(id) {
        selectedUsers.delete(id);
        $(`.user-item[data-id="${id}"]`).removeClass('selected').find('i').removeClass('fa-check-circle text-success').addClass('fa-circle-o');
        $(`.selected-user-row[data-id="${id}"]`).remove();
        updateUI();
    };

    $('#clearAllSelected').click(function() {
        selectedUsers.clear();
        $('#selectedUsersList').empty();
        $('.user-item').removeClass('selected').find('i').removeClass('fa-check-circle text-success').addClass('fa-circle-o');
        updateUI();
    });

    function updateUI() {
        let count = selectedUsers.size;
        $('#selectedCount, #modalSelectedCount').text(count);
        
        // Update main page preview
        $('#selectedUserPreview').empty();
        let i = 0;
        selectedUsers.forEach(id => {
            if (i < 5) {
                let name = $(`.selected-user-row[data-id="${id}"] .name`).text();
                $('#selectedUserPreview').append(`<span class="selected-tag">${name}</span>`);
            }
            i++;
        });
        if (count > 5) $('#selectedUserPreview').append(`<span class="selected-tag">+${count-5} more</span>`);

        // Update hidden inputs
        $('input[name="user_ids[]"]').remove();
        selectedUsers.forEach(id => {
            $('#notificationForm').append(`<input type="hidden" name="user_ids[]" value="${id}">`);
        });
    }

    // Close suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#actionValueSection').length) {
            $('#suggestionBox').hide();
        }
    });

    // Form Submit
    $('#notificationForm').submit(function() {
        let target = $('#targetType').val();
        if (target === 'custom' && selectedUsers.size === 0) {
            toastr.error('Please select at least one user');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Dispatching...');
        return true;
    });
});
</script>
@endpush
