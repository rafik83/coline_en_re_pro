/**
 * Created by SBoussacsou on 23/01/14.
 */
function validate_don(id) {
    $.ajax({
        url: "/intersa/ajax/validate/"+id ,
        beforeSend: function( xhr ) {
           ;
        }
    })
        .done(function( data ) {
        $("#validate_don").html(data);
        $("#validate_don").modal('show');
        });
}

function checkdonateur(id) {
    $.ajax({
        url: "/intersa/donateur/ajax/delete/"+id ,
        beforeSend: function( xhr ) {
            ;
        }
    })
        .done(function( data ) {
            $("#validate_don").html(data);
            $("#validate_don").modal('show');
        });
}

function delete_user(id) {
    $.ajax({
        url: "/intersa/users/ajax/delete/"+id ,
        beforeSend: function( xhr ) {
            xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
        }
    })
        .done(function( data ) {
            $("#deleteuser").html(data);
            $("#deleteuser").modal('show');
        });
}