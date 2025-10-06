<header style="position: fixed; top: 0; z-index: 3; width: 100%; background-color: #0a2332cc; height: 75px; border-bottom: 1px solid #34d39956; overflow: visible;">


    <div style="width: 95%; height: 100%; max-width: 1200px; display: flex; justify-content: space-between; align-items: center; margin: 0 auto;">
        <div onclick="openProfile(event)" class="user_container" style="display:flex; justify-content: space-between; align-items: center; color: #34D399; gap: 0.6rem; font-size: 17px; padding: 12px 12px; border-radius: 8px; cursor: pointer;">
            <i class="fa-solid fa-user"></i>
            <p style="font-weight:600;"><?= $nome; ?></p>
            <i class="fa fa-chevron-right" style="transform: rotate(90deg); font-size: 14px; "></i>
            <div class="menu-profile" style="position: absolute; top:64px; z-index:4; transform: translateX(-12px); display:none; flex-direction: column; justify-content: space-around; background-color: #0a2332; font-size: 17px; padding: 8px 0px; border-radius: 8px; cursor: pointer; min-width: 180px; border: 1px solid #34d39956;">
                <p onclick="openEditModal()" class="edit-profile"><i class="fa-solid fa-pen" style="margin-right: 12px; font-size: 15px; vertical-align: middle; padding: 12px;"></i>Editar Perfil</p>
                <p onclick="window.location.href='/logout';" class="button-logout" style="color: #F87171"> <i class="fa-solid fa-arrow-right-from-bracket" style="margin-right: 12px; font-size: 15px; vertical-align: middle;padding: 12px;"></i>Sair</p>
            </div>

            <style>
                .user_container:hover {
                    background-color: rgba(16, 185, 129, .1);
                }

                .edit-profile:hover {
                    background-color: rgba(16, 185, 129, .1);
                }

                .button-logout:hover {
                    background-color: rgba(255, 0, 0, .1);
                }
            </style>
            <script>
                const openProfile = (event) => {
                    const menuProfile = document.querySelector('.menu-profile');

                    if (menuProfile.style.display === 'flex') {
                        menuProfile.style.display = 'none';
                    } else {
                        menuProfile.style.display = 'flex';
                    }

                    event.stopPropagation();
                };

                document.addEventListener('click', (event) => {
                    const menuProfile = document.querySelector('.menu-profile');
                    const userContainer = document.querySelector('.user_container');

                    if (!menuProfile.contains(event.target) && !userContainer.contains(event.target)) {
                        menuProfile.style.display = 'none';
                    }
                });
            </script>
        </div>

        <div style="width: 40%; max-width: 200px; display: flex; align-items: center; justify-content: space-between;">

            <div style="color: #34D399;">
                R$ <?= number_format($saldo, 2, ',', ''); ?>
            </div>

            <div style="color: #34D399;">
                <i onclick="getNotification()" class="fa-solid fa-bell" style="font-size: 20px; cursor:pointer;"></i>
            </div>

            <script>
                const getNotification = () => {
                    Notiflix.Loading.standard();
                    Notiflix.Loading.remove(1000);
                    setTimeout(() => {
                        Notiflix.Notify.success('Nenhuma notificação!')
                    }, 1500)
                }
            </script>

            <div id="volumeIcon" style="color: #34D399;">
                <i class="fa-solid fa-volume-high" style="font-size: 18px; cursor:pointer;"></i>
            </div>

            <audio id="bgMusic" loop>
                <source src="/assets/music.mp3" type="audio/mpeg">
            </audio>

            <script>
                let audio = document.getElementById('bgMusic');
                let volumeIcon = document.getElementById('volumeIcon').querySelector('i');
                let isPlaying = false;
                audio.volume = 0.3;

                const startMusicOnFirstClick = () => {
                    if (!isPlaying) {
                        audio.play().catch(error => console.log("Erro ao iniciar o áudio:", error));
                        isPlaying = true;
                        document.removeEventListener('click', startMusicOnFirstClick);
                    }
                };

                document.addEventListener('click', startMusicOnFirstClick);

                volumeIcon.addEventListener('click', () => {
                    if (audio.paused) {
                        audio.play();
                        volumeIcon.classList.replace('fa-volume-xmark', 'fa-volume-high');
                        Notiflix.Notify.success('Música Ativada');
                    } else {
                        audio.pause();
                        Notiflix.Notify.info('Música Desativada');
                        volumeIcon.classList.replace('fa-volume-high', 'fa-volume-xmark');
                    }
                });
            </script>



        </div>

    </div>
</header>