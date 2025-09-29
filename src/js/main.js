import Swiper from "swiper";

document.addEventListener("DOMContentLoaded", () => {
    const cases = document.querySelectorAll('.cases__item-slider');

    cases.forEach((elem)=>{
        new Swiper(elem, {
            
        })
    })
});