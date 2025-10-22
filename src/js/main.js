import GLightbox from 'glightbox';

document.addEventListener("DOMContentLoaded", () => {
    const header = document.querySelector('.header');
    const modalList = document.querySelectorAll('[data-modal]');
    const btnCloseList = document.querySelectorAll('.form-popup__close');
    const galeryOpenBtnList = document.querySelectorAll('[data-galery]');
    const forms = document.querySelectorAll('[data-send]');
    const modals = document.querySelectorAll('.form-popup');

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            const target = event.target;
            if (target.closest('.form-popup__elem')) return;
            else if (target.closest('.form-popup__close')) return;
            else {
                modal.classList.remove('active');
                document.body.style.overflow = null;
                document.body.style.paddingRight = null;
                header.style.width = null;
            }
        })
    });

    modalList.forEach((elem) => {
        elem.addEventListener('click', (e) => {
            const id = elem.dataset.modal;
            if (!id) {
                throw new Error("Modal is not defined!");
            }
            const modal = document.getElementById(id);
            let paddingOffset = window.innerWidth - document.body.offsetWidth + 'px';
            document.body.style.overflow = "hidden";
            document.body.style.paddingRight = paddingOffset;
            header.style.width = `calc(100% - ${paddingOffset})`;
            modal.classList.add('active')
        })
    });

    btnCloseList.forEach(elem => {
        elem.addEventListener('click', (event) => {
            const target = event.target;
            const modal = target.closest('.form-popup');
            modal.classList.remove('active');
            document.body.style.overflow = null;
            document.body.style.paddingRight = null;
            header.style.width = null;
        })
    });

    galeryOpenBtnList.forEach(openBtn => {
        let gallery = null;
        openBtn.addEventListener('click', () => {
            if (gallery) {
                gallery.open();
            } else {
                const data = openBtn.dataset.galery;
                if (!data) {
                    throw new Error("data id is not defined!");
                }
                const arrayData = JSON.parse(data.replace(/'/g, '"'));
                const elements = arrayData.map(elem => ({
                    'href': elem,
                    'type': 'image',
                }));

                gallery = GLightbox({
                    elements: elements,
                    autoplayVideos: true,
                });
                gallery.on('open', () => {
                    let paddingOffset = window.innerWidth - document.body.offsetWidth + 'px';
                    header.style.width = `calc(100% - ${paddingOffset})`;
                });

                gallery.on('close', () => {
                    header.style.width = null;
                });

                gallery.open();
            }
        })
    });

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            fetch('/send.php', {
                method: 'POST',
                body: formData
            }).then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location = '/thank.html';
                    } else {
                        alert(data.message)
                    }
                }).catch(err => {
                    alert('Упс что пошло не так. Попробуйте позже!')
                    console.log(err);
                })
        })
    })
});