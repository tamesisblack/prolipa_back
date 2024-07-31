<!-- <aside class="left-sidebar">
    <div class="scroll-sidebar">
        <div class="user-profile" style="background: url(../assets/images/background/user-info.jpg) no-repeat;">
            <div class="profile-img"> <img src="../assets/images/users/profile.png" alt="user" /> </div>
            <div class="profile-text"> <a href="#" class="dropdown-toggle u-dropdown" data-toggle="dropdown"
                    role="button" aria-haspopup="true" aria-expanded="true">Markarn Doe</a>
                <div class="dropdown-menu animated flipInY"> <a href="#" class="dropdown-item"><i class="ti-user"></i>
                        My Profile</a> <a href="#" class="dropdown-item"><i class="ti-wallet"></i> My Balance</a> <a
                        href="#" class="dropdown-item"><i class="ti-email"></i> Inbox</a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item"><i class="ti-settings"></i>
                        Account Setting</a>
                    <div class="dropdown-divider"></div> <a href="login.html" class="dropdown-item"><i
                            class="fa fa-power-off"></i> Logout</a>
                </div>
            </div>
        </div> 
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="nav-small-cap">Menu</li>

                <?php
                if (auth()->user()->id_group == 1 ||  auth()->user()->id_group == 5 ||  auth()->user()->id_group == 8) {
                    ?>

                <li @click="menu=26"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-tachometer"></i><span class="hide-menu text-light">Dashboard Plataforma</span></a>
                </li>
                <li @click="menu=2"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-university"></i><span class="hide-menu text-light">Institución</span></a>
                </li>
                <li @click="menu=1"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-users"></i><span class="hide-menu text-light">Usuarios</span></a>
                </li>
                <li @click="menu=3"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-user-tie"></i><span class="hide-menu text-light">Vendedor</span></a>
                </li>

                <?php
                }
                ?>

                <?php
                    if (auth()->user()->id_group == 1) {
                ?>
                <li @click="menu=27"> <a class="waves-effect waves-dark" aria-expanded="true"><i
                            class="fa fa-file"></i><span class="hide-menu text-light">Contenido</span></a>
                </li>
                <li @click="menu=22"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-cog"></i><span class="hide-menu text-light">Período Escolar</span></a>
                </li>
                <li @click="menu=21"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-layer-group"></i><span class="hide-menu text-light">Asignaturas</span></a>
                </li>
                <?php
                    }
                ?>
                
                <?php
                if (auth()->user()->id_group == 6 ||  auth()->user()->id_group == 1 ||  auth()->user()->id_group == 5 || auth()->user()->id_group == 8) {
                    ?>
                <li @click="menu=25"> <a class="waves-effect waves-dark" aria-expanded="true"><i
                            class="fa fa-window-maximize"></i><span class="hide-menu text-light">Mis Clases</span></a>
                </li>
                <li @click="menu=5"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-book"></i><span class="hide-menu text-light">Libros</span></a>
                </li>
                <li @click="menu=6"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="mdi mdi-book"></i><span class="hide-menu text-light">Cuadernos</span></a>
                </li>
                <li @click="menu=8"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-file-o"></i><span class="hide-menu text-light">Guías</span></a>
                </li>
                <li @click="menu=20"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fas fa-book-open"></i><span class="hide-menu text-light">Plan Lector</span></a>
                </li>
                <li @click="menu=7"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="mdi mdi-file-word"></i><span class="hide-menu text-light">Planificación</span></a>
                </li>
                <li @click="menu=9"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-file"></i><span class="hide-menu text-light">Material de Apoyo</span></a>
                </li>
                <li @click="menu=10"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fab fa-youtube"></i><span class="hide-menu text-light">Video</span></a>
                </li>
                <li @click="menu=11"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-music"></i><span class="hide-menu text-light">Audio</span></a>
                </li>
                <?php
                }
                ?>

                <?php
                if (auth()->user()->id_group == 1) {
                    ?>
                <li @click="menu=13"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Libro</span></a>
                </li>
                <li @click="menu=14"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Cuaderno</span></a>
                </li>
                <li @click="menu=15"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Guía</span></a>
                </li>
                <li @click="menu=12"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Plan Lector</span></a>
                </li>
                <li @click="menu=16"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Planificación</span></a>
                </li>
                <li @click="menu=17"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Material de
                            Apoyo</span></a>
                </li>
                <li @click="menu=18"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Video</span></a>
                </li>
                <li @click="menu=19"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-plus"></i><span class="hide-menu text-light">Registrar Audio</span></a>
                </li>
                <li @click="menu=28"> <a class="waves-effect waves-dark" aria-expanded="false"><i
                            class="fa fa-play"></i><span class="hide-menu text-light">Registrar Juegos</span></a>
                </li>
                <?php
                }
                ?>
            </ul>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="#" class="link" data-toggle="tooltip" title="Settings"><i class="ti-settings"></i></a>
        <?php
            if (auth()->user()->id_group == 1 || auth()->user()->id_group == 0) {
        ?>
        <a href="#" @click="menu=24" class="link" data-toggle="tooltip" title="Chat"><i
                class="fa fa-comments-o"></i></a>
        <?php
            }
        ?>
        <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();" class="link"
            data-toggle="tooltip" title="Salir"><i class="mdi mdi-power"></i></a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside> -->