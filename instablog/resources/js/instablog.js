$(function() {
  $('#toggleAllAuthors').click (function () {
     var checkedStatus = this.checked;
    $('#authors').find(':checkbox').each(function () {
        $(this).prop('checked', checkedStatus);
     });
  });
  
  $('#toggleAllPosts').click (function () {
     var checkedStatus = this.checked;
    $('#posts').find(':checkbox').each(function () {
        $(this).prop('checked', checkedStatus);
     });
  });
  
});