/*Переход по вкладкам корзины*/
$(document).on( "click", ".continue", function(e) {
	var section=$(this).closest("li.section");
	var emptyIput;
	/*Получаем элементы обязательные для заполнения*/
	$('input:required',section).each(function(i,input){
		if (!input.checkValidity()) {
			emptyIput=input;
			return false;
		};
	})
	if (!emptyIput){
		section.removeClass('active');
		section.next().addClass('active');
	}else{
		emptyIput.reportValidity();
	} 
});