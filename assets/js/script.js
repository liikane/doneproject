(function(){
    // Simple JS hook for DoneProject
    document.addEventListener('DOMContentLoaded', function(){
        var elems = document.querySelectorAll('.doneproject-greeting');
        elems.forEach(function(el){
            el.addEventListener('click', function(){
                el.style.opacity = '0.8';
                setTimeout(function(){ el.style.opacity = '1'; }, 200);
            });
        });
    });
})();
