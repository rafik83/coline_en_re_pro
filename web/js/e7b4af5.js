/**
 * Created by SBoussacsou on 23/01/14.
 */
function delete_user(id) {
    $.ajax({
        url: "/admin/ajax/delete/"+id ,
        beforeSend: function( xhr ) {
            xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
        }
    })
        .done(function( data ) {
        $("#deleteuser").html(data);
        });
}