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
                    <span>Send Notification</span>
                </li>
            </ul>
        </div>

        <h3 class="page-title">Send Push Notification</h3>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-body">
                        <form id="notificationForm" method="POST" action="{{ route('admin.notifications.send') }}">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group @error('title') has-error @enderror">
                                        <label>Notification Title <span class="required">*</span></label>
                                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                        @error('title')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group @error('message') has-error @enderror">
                                        <label>Notification Message <span class="required">*</span></label>
                                        <textarea name="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!--<div class="form-group @error('image') has-error @enderror">-->
                                    <!--    <label>Image URL (Optional)</label>-->
                                    <!--    <input type="url" name="image" class="form-control" value="{{ old('image') }}" placeholder="https://example.com/image.jpg">-->
                                    <!--    @error('image')-->
                                    <!--        <span class="help-block">{{ $message }}</span>-->
                                    <!--    @enderror-->
                                    <!--</div>-->
                                </div>
                                <div class="col-md-4">
                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-info-circle"></i>
                                                <span class="caption-subject">Preview</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body text-center">
                                            <div id="notificationPreview" style="background: #f5f5f5; padding: 15px; border-radius: 10px;">
                                                <strong id="previewTitle">Notification Title</strong>
                                                <p id="previewMessage" style="margin-top: 10px; color: #666;">Notification message will appear here...</p>
                                                <div id="previewImageContainer" style="display: none; margin-top: 10px;">
                                                    <img id="previewImage" src="" style="max-width: 100%; max-height: 100px; border-radius: 5px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group @error('target_type') has-error @enderror">
                                <label>Target Audience <span class="required">*</span></label>
                                <select name="target_type" id="targetType" class="form-control" required>
                                    <option value="">Select Audience</option>
                                    <option value="all">All Users</option>
                                    <option value="companies">All Companies</option>
                                    <option value="students">All Students</option>
                                    <option value="professionals">All Professionals</option>
                                    <option value="subscribed">Subscribed Companies (Active Packages)</option>
                                    <option value="custom">Custom Selection</option>
                                </select>
                                @error('target_type')
                                    <span class="help-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div id="customUserSection" style="display: none;">
                                <div class="form-group">
                                    <label>Select Users</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" id="userSearch" class="form-control" placeholder="Search users by name or email...">
                                        </div>
                                        <div class="col-md-2">
                                            <select id="userTypeFilter" class="form-control">
                                                <option value="all">All Types</option>
                                                <option value="company">Companies</option>
                                                <option value="student">Students</option>
                                                <option value="professional">Professionals</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="searchUsersBtn" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="searchResults" class="form-group" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e5e5; padding: 10px; display: none;">
                                    <!-- Search results will appear here -->
                                </div>

                                <div class="form-group">
                                    <label>Selected Users (<span id="selectedCount">0</span>)</label>
                                    <div id="selectedUsers" style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e5e5; padding: 10px;">
                                        <!-- Selected users will appear here -->
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="fa fa-send"></i> Send Notification
                                </button>
                                <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    .user-item {
        padding: 8px;
        margin: 5px 0;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        cursor: pointer;
    }
    .user-item:hover {
        background: #f0f0f0;
    }
    .user-item.selected {
        background: #e8f5e9;
        border-color: #4caf50;
    }
    .selected-user {
        display: inline-block;
        margin: 3px;
        padding: 5px 10px;
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 20px;
    }
    .selected-user .remove {
        margin-left: 5px;
        color: #f44336;
        cursor: pointer;
    }
    .badge {
        margin-left: 5px;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 10px;
    }
    .badge-company { background: #4caf50; color: white; }
    .badge-student { background: #2196f3; color: white; }
    .badge-professional { background: #ff9800; color: white; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    let selectedUsers = new Set();
    let searchTimeout;

    // Preview functionality
    $('input[name="title"]').on('input', function() {
        $('#previewTitle').text($(this).val() || 'Notification Title');
    });

    $('textarea[name="message"]').on('input', function() {
        $('#previewMessage').text($(this).val() || 'Notification message will appear here...');
    });

    $('input[name="image"]').on('input', function() {
        let url = $(this).val();
        if (url) {
            $('#previewImage').attr('src', url);
            $('#previewImageContainer').show();
        } else {
            $('#previewImageContainer').hide();
        }
    });

    // Target type change
    $('#targetType').change(function() {
        if ($(this).val() === 'custom') {
            $('#customUserSection').slideDown();
        } else {
            $('#customUserSection').slideUp();
        }
    });

    // Search users
    function searchUsers() {
        let query = $('#userSearch').val();
        let type = $('#userTypeFilter').val();

        $.ajax({
            url: '{{ route("admin.notifications.users.search") }}',
            data: { q: query, type: type },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(user => {
                        let userClass = user.usertype === 'company' ? 'badge-company' : 
                                       (user.usertype === 'student' ? 'badge-student' : 'badge-professional');
                        let isSelected = selectedUsers.has(user.id) ? 'selected' : '';
                        let disabled = !user.has_fcm || !user.push_enabled ? 'disabled' : '';
                        
                        html += `
                            <div class="user-item ${isSelected}" data-id="${user.id}" data-name="${user.name}" data-type="${user.usertype}" ${disabled}>
                                <strong>${user.name}</strong> 
                                <span class="badge ${userClass}">${user.usertype}</span>
                                <br>
                                <small>${user.email}</small>
                                ${!user.has_fcm ? '<span class="label label-danger">No FCM</span>' : ''}
                                ${!user.push_enabled ? '<span class="label label-warning">Push Disabled</span>' : ''}
                            </div>
                        `;
                    });
                    $('#searchResults').html(html).slideDown();
                }
            }
        });
    }

    $('#searchUsersBtn').click(searchUsers);

    $('#userSearch, #userTypeFilter').on('input change', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchUsers, 500);
    });

    // Select/deselect users
    $(document).on('click', '.user-item:not(.disabled)', function() {
        let userId = $(this).data('id');
        let userName = $(this).data('name');
        let userType = $(this).data('type');

        if (selectedUsers.has(userId)) {
            selectedUsers.delete(userId);
            $(this).removeClass('selected');
            removeSelectedUser(userId);
        } else {
            selectedUsers.add(userId);
            $(this).addClass('selected');
            addSelectedUser(userId, userName, userType);
        }

        updateSelectedCount();
        updateUserIdsInput();
    });

    function addSelectedUser(id, name, type) {
        let badgeClass = type === 'company' ? 'badge-company' : 
                         (type === 'student' ? 'badge-student' : 'badge-professional');
        let html = `
            <span class="selected-user" data-id="${id}">
                ${name} <span class="badge ${badgeClass}">${type}</span>
                <span class="remove" onclick="removeSelectedUser(${id})">&times;</span>
            </span>
        `;
        $('#selectedUsers').append(html);
    }

    window.removeSelectedUser = function(id) {
        selectedUsers.delete(id);
        $(`.user-item[data-id="${id}"]`).removeClass('selected');
        $(`.selected-user[data-id="${id}"]`).remove();
        updateSelectedCount();
        updateUserIdsInput();
    };

    function updateSelectedCount() {
        $('#selectedCount').text(selectedUsers.size);
    }

    function updateUserIdsInput() {
        // Remove existing hidden inputs
        $('input[name="user_ids[]"]').remove();
        
        // Add new hidden inputs
        selectedUsers.forEach(id => {
            $('#notificationForm').append(`<input type="hidden" name="user_ids[]" value="${id}">`);
        });
    }

    // Form submission
    $('#notificationForm').submit(function(e) {
        let targetType = $('#targetType').val();
        
        if (!targetType) {
            e.preventDefault();
            toastr.error('Please select target audience');
            return false;
        }

        if (targetType === 'custom' && selectedUsers.size === 0) {
            e.preventDefault();
            toastr.error('Please select at least one user');
            return false;
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
        return true;
    });
});
</script>
@endpush