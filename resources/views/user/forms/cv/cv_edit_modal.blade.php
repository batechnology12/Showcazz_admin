<div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
    <div class="modal-header">                
        <h4 class="modal-title">{{__('Edit CV')}}</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

        <form class="form" id="add_edit_profile_cv" method="PUT" action="{{ route('update.front.profile.cv', [$profileCv->id, $user->id]) }}">
            {{csrf_field()}}
            <input type="hidden" name="id" id="id" value="{{$profileCv->id}}"/>
            
            @include('user.forms.cv.cv_form')
            <div class="modal-footer">
                <button type="button" class="btn btn-large btn-primary" onClick="submitProfileCvForm();">{{__('Save Changes')}} <i class="fa fa-arrow-circle-right" aria-hidden="true"></i></button>
            </div>
        </form>
    </div>
    <!-- /.modal-content --> 
</div>
<!-- /.modal-dialog -->