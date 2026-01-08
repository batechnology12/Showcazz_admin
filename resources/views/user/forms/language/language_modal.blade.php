<div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
        <form class="form" id="add_edit_profile_language" method="POST" action="{{ route('store.front.profile.language', [$user->id]) }}">{{ csrf_field() }}
            <div class="modal-header">
            
                <h4 class="modal-title">{{__('Add Language')}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @include('user.forms.language.language_form')
            <div class="modal-footer">
                <button type="button" class="btn btn-large btn-primary" onClick="submitProfileLanguageForm();">{{__('Save Changes')}} <i class="fa fa-arrow-circle-right" aria-hidden="true"></i></button>
            </div>
        </form>
    </div>
    <!-- /.modal-content --> 
</div>
<!-- /.modal-dialog -->