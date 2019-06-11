
$(function()
{
    function after_form_submitted(data) 
    {
        if(data.result == 'success')
        {
            $('form#reused_form').hide();
            $('#success_message').show();
            $('#error_message').hide();
            window.location.href = "https://www.gazer.com/promo/sucsess.html"
        }
        else
        {
            $('#error_message').append('<ul></ul>');

            jQuery.each(data.errors,function(key,val)
            {
                $('#error_message ul').append('<li>'+key+':'+val+'</li>');
            });
            $('#success_message').hide();
            $('#error_message').show();

            //reverse the response on the button
            $('button[type="button"]', $form).each(function()
            {
                $btn = $(this);
                label = $btn.prop('orig_label');
                if(label)
                {
                    $btn.prop('type','submit' ); 
                    $btn.text(label);
                    $btn.prop('orig_label','');
                }
            });
            
        }//else
    }

	$('#reused_form').submit(function(e)
      {
        e.preventDefault();

        $form = $(this);
        //show some response on the button
        $('button[type="submit"]', $form).each(function()
        {
            $btn = $(this);
            $btn.prop('type','button' ); 
            $btn.prop('orig_label',$btn.text());
            $btn.text('Sending ...');
        });
        

            $.ajax({
                type: "POST",
                url: 'handler.php',
                data: $form.serialize(),/*+ "Custmail=" + $('#email').val()*/
                //data: {name1: 1, name2: 2}
                /*data: {
                        counter: value,
                        $form.serialize(),
                },*/
                success: after_form_submitted,
                dataType: 'json'
            });       
        
      });

    var contentAllo;
    /*var contentBaza;*/
    $(".allo.store").click(function(){
        $(".store.active").removeClass("active");
        $("#Ablock").append('<div class="wrap-allo"><h5>Оберіть модель реєстратора, яку плануєте придбати в АЛЛО зі знижкою:</h5><div class="half"><label class="container" for="radio">F225<input type="radio" name="dashcam" value="F225"><span class="checkmark"></span></label><label class="container" for="radio">F735g<input type="radio" name="dashcam" value="F735g"><span class="checkmark"></span></label><label class="container" for="radio">F720<input type="radio" name="dashcam" value="F720"><span class="checkmark"></span></label><label class="container" for="radio">F715<input type="radio" name="dashcam" value="F715"><span class="checkmark"></span></label><label class="container" for="radio">F230w<input type="radio" name="dashcam" value="F230w"><span class="checkmark"></span></label></div><div class="half"><label class="container" for="radio">F122<input type="radio" name="dashcam" value="F122"><span class="checkmark"></span></label><label class="container" for="radio">F117<input type="radio" name="dashcam" value="F117"><span class="checkmark"></span></label><label class="container" for="radio">F150<input type="radio" name="dashcam" value="F150"><span class="checkmark"></span></label><label class="container" for="radio">F525<input type="radio" name="dashcam" value="F525"><span class="checkmark"></span></label><label class="container" for="radio">H521<input type="radio" name="dashcam" value="H521"><span class="checkmark"></span></label></div></div>');
        contentAllo = $('div.wrap-baza').detach();
        contentAllo = $('div.wrap-eldorado').detach();
        contentAllo = $('div.wrap-ua130').detach();
        contentAllo = $('div.wrap-foxtrot').detach();
        $(this).toggleClass("active");
    });
    /*$(".allo.store").dblclick(function() {
        contentAllo = $('div.wrap-allo').detach();
    });*/
    $(".baza.store").click(function(){
        $(".store.active").removeClass("active");
        $("#Ablock").append('<div class="wrap-baza"><h5>Отримати знижку 20% на будь-яку продукцію Gazer в мережі База Автозвуку:</h5><label class="container ba" for="radio">Отримати знижку на всі товари Gazer в База Автозвуку<input type="radio" name="dashcam" value="BAZA"><span class="checkmark"></span></label></div>');
        contentAllo = $('div.wrap-allo').detach();
        contentAllo = $('div.wrap-eldorado').detach();
        contentAllo = $('div.wrap-ua130').detach();
        contentAllo = $('div.wrap-foxtrot').detach();
        $(this).toggleClass("active");
    });
    $(".eldorado.store").click(function(){
        $(".store.active").removeClass("active");
        $("#Ablock").append('<div class="wrap-eldorado"><h5>Отримати знижку 20% на відеореєстратори Gazer в мережі ЕЛЬДОРАДО:</h5><label class="container el" for="radio"><input id="ChangeCode" type="radio" name="dashcam" value="ELDORADO" data-id="359"><span class="checkmark" id="itis"></span>Отримати знижку в ELDORADO</label></div>');
        contentAllo = $('div.wrap-allo').detach();
        contentAllo = $('div.wrap-baza').detach();
        contentAllo = $('div.wrap-ua130').detach();
        contentAllo = $('div.wrap-foxtrot').detach();
        $(this).toggleClass("active");
    });
    $(".ua130.store").click(function(){
        $(".store.active").removeClass("active");
        $("#Ablock").append('<div class="wrap-ua130"><h5>Отримати знижку 20% на будь-яку продукцію Gazer в мережі 130.ua:</h5><label class="container ua130" for="radio"><input type="radio" name="dashcam" value="UA130"><span class="checkmark"></span>Отримати знижку на всі товари Gazer в 130.ua</label></div>');
        contentAllo = $('div.wrap-allo').detach();
        contentAllo = $('div.wrap-baza').detach();
        contentAllo = $('div.wrap-eldorado').detach();
        contentAllo = $('div.wrap-foxtrot').detach();
        $(this).toggleClass("active");
    });
    $(".foxtrot.store").click(function(){
        $(".store.active").removeClass("active");
        $("#Ablock").append('<div class="wrap-foxtrot"><h5>Отримати знижку 20% на відеореєстратори Gazer в мережі FOXTROT:</h5><label class="container ua130" for="radio"><input type="radio" name="dashcam" value="FOXTROT"><span class="checkmark"></span>Отримати знижку в FOXTROT</label></div>');
        contentAllo = $('div.wrap-allo').detach();
        contentAllo = $('div.wrap-baza').detach();
        contentAllo = $('div.wrap-eldorado').detach();
        contentAllo = $('div.wrap-ua130').detach();
        $(this).toggleClass("active");
    });

    $( "button" ).click(
        function() 
        { 
            $.ajax(
                {
                    type: 'POST',
                    url: 'log.php',
                    data: "log=" + $('#name').val() + " ; " + $('#email').val() + " ; " + $('#phone').val() + " ; " + $('input[type="radio"]').val(),
                    //success: after_form_submitted,
                    //data: "log=" + $form.serialize()
                }
            );
            /*$.ajax(
                {
                    type: "POST",
                    url: 'handler.php',
                    data: "CustMAIL=" + $('#email').val(),
                }
            );*/
        }

    );
    /*if ($('#itis').attr('background-color')==='#00a651'){
     $("button").click(
            function() 
            { 
                //var fruitCount = $(this).data('fruit');
                $.ajax(
                    {
                        type: 'POST',
                        url: 'count_file.php',
                        data: "LastCount=" + $('#ChangeCode').attr('data-id')+ $('#ChangeCode').val('data-id'),
                        //success: after_form_submitted,
                    }
                );

            }
        );
    }*/


});
