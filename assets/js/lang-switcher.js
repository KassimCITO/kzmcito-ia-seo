document.addEventListener('click', function(e){
    if(e.target.dataset.lang){
        document.cookie = 'kzmcito_lang=' + e.target.dataset.lang + ';path=/';
        location.reload();
    }
});
