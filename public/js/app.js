$(document).ready(function(){

	/* Below code is mostly required in every project, so please don't remove it */
	// $.ajaxSetup({
    //     headers: {
    //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //     }
    // });
    
    // var picker = new CP(document.querySelector('input[name="pitch_color"]'));
    var picker2 = new CP(document.querySelector('input[name="btn_color"]'));
    var picker3 = new CP(document.querySelector('input[name="btn_color_hover"]'));

    // picker.on("drag", function(color) {
    //     this.target.value = '#' + color;
    // });

    picker2.on("drag", function(color) {
        this.target.value = '#' + color;
    });
    
    picker3.on("drag", function(color) {
        this.target.value = '#' + color;
    });
    

    //Handling tabs click event
    $('ul.tabs li').click(function () {

        var tab_id = $(this).children().attr('href');

        $('ul.tabs li').removeClass('active');
        $('.tab-content').removeClass('current');

        $(this).addClass('active');
        $(tab_id).addClass('current');
    });

});