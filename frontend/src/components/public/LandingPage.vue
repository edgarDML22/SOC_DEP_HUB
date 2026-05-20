<template>
    <div class="min-h-screen bg-neutral-950 text-white overflow-x-hidden font-sans">
        <!-- Navbar -->
        <header class="fixed top-0 left-0 w-full z-50 backdrop-blur-md bg-black/40 border-b border-white/10">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-wide">
                        <span class="text-white">Britania </span>
                        <span class="text-red-500">Club</span>
                    </h1>
                </div>

                <nav class="hidden md:flex gap-8 text-xs font-bold uppercase tracking-wider text-neutral-350">
                    <a href="#about" class="hover:text-blue-400 transition-colors">Nosotros</a>
                    <a href="#sports" class="hover:text-red-400 transition-colors">Deportes</a>
                    <a href="#benefits" class="hover:text-blue-400 transition-colors">Beneficios</a>
                    <a href="#gallery" class="hover:text-red-400 transition-colors">Galería</a>
                    <a href="#contact" class="hover:text-blue-400 transition-colors">Contacto</a>
                </nav>

                <div class="flex items-center gap-3">
                    <button @click="openLink('/torneos')"
                        class="hidden sm:flex items-center gap-1.5 border border-white/20 hover:border-blue-500/50 hover:bg-white/5 text-white px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer">
                        <i class="fas fa-trophy text-blue-400"></i>
                        Pre-Registro Torneos
                    </button>
                    <button @click="openLink('/login')"
                        class="bg-red-600 hover:bg-red-500 text-white px-5 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shadow-lg shadow-red-600/25">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative h-screen flex items-center justify-center bg-cover bg-center"
            style="background-image: url('https://images.unsplash.com/photo-1517649763962-0c623066013b?q=80&w=2070&auto=format&fit=crop');">
            <div class="absolute inset-0 bg-neutral-950/80"></div>

            <div class="relative z-10 text-center px-6 max-w-4xl">
                <p class="uppercase tracking-[0.4em] text-blue-400 mb-4 text-xs font-black">
                    Deporte • Familia • Comunidad
                </p>

                <h1 class="text-4xl md:text-7xl font-black leading-tight mb-6 tracking-tight text-white">
                    Vive la Experiencia <span class="text-red-500">Britania</span>
                </h1>

                <p class="text-base md:text-lg text-neutral-300 mb-10 leading-relaxed max-w-2xl mx-auto">
                    Disfruta de instalaciones deportivas de primer nivel, espacios familiares exclusivos
                    y una comunidad apasionada por el bienestar, la recreación y el alto rendimiento.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button @click="openLink('/torneos')"
                        class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-full font-bold transition-all shadow-lg shadow-blue-600/25 cursor-pointer flex items-center justify-center gap-2">
                        <i class="fas fa-trophy text-base text-white"></i>
                        Pre-Registro de Torneos
                    </button>

                    <button @click="openLink('/login')"
                        class="border border-red-500 hover:bg-red-600 text-white px-8 py-4 rounded-full font-semibold transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="fas fa-sign-in-alt text-base text-red-400"></i>
                        Portal de Socios / Admin
                    </button>
                </div>
            </div>
        </section>

        <!-- About -->
        <section id="about" class="py-28 bg-neutral-900 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <img src="https://images.unsplash.com/photo-1526232761682-d26e03ac148e?q=80&w=2070&auto=format&fit=crop"
                        class="rounded-3xl shadow-2xl object-cover h-[450px] w-full border border-white/10" />
                </div>

                <div class="space-y-6">
                    <p class="uppercase tracking-[0.3em] text-red-500 text-xs font-black">
                        Sobre el Club
                    </p>

                    <h2 class="text-3xl md:text-5xl font-black mb-6 leading-tight tracking-tight">
                        Un espacio diseñado para tu bienestar y familia
                    </h2>

                    <p class="text-neutral-300 leading-relaxed text-base">
                        Britania Club combina instalaciones deportivas premium con elegantes áreas sociales,
                        creando un entorno único donde nuestros miembros pueden entrenar, competir, relajarse
                        y conectar con una gran comunidad.
                    </p>

                    <p class="text-neutral-400 leading-relaxed text-sm">
                        Desde canchas profesionales de tenis y piscinas semiolímpicas, hasta eventos sociales exclusivos,
                        cada detalle está pensado para ofrecer el máximo confort y exclusividad.
                    </p>

                    <div class="grid grid-cols-2 gap-6 pt-4">
                        <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
                            <h3 class="text-3xl font-black text-blue-400 mb-1">20+</h3>
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-400">Disciplinas</p>
                        </div>

                        <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
                            <h3 class="text-3xl font-black text-red-500 mb-1">5,000+</h3>
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-400">Socios Activos</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sports -->
        <section id="sports" class="py-28 bg-neutral-950 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <p class="uppercase tracking-[0.3em] text-blue-400 text-xs font-black">
                        Deportes y Actividades
                    </p>

                    <h2 class="text-3xl md:text-5xl font-black tracking-tight">
                        Nuestras Instalaciones
                    </h2>

                    <p class="text-neutral-400 text-base max-w-lg mx-auto">
                        Descubre los espacios de primer nivel que tenemos preparados para ti, tu familia y atletas de todos los niveles.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(sport, index) in sports" :key="sport.title"
                        class="bg-neutral-900 border border-white/10 rounded-3xl overflow-hidden hover:-translate-y-2 transition-all duration-300 group"
                        :class="index % 2 === 0 ? 'hover:border-blue-500/30' : 'hover:border-red-500/30'">
                        <div class="overflow-hidden h-60">
                            <img :src="sport.image" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>

                        <div class="p-6 space-y-2">
                            <h3 class="text-xl font-extrabold group-hover:text-white transition-colors"
                                :class="index % 2 === 0 ? 'text-blue-400' : 'text-red-400'">
                                {{ sport.title }}
                            </h3>
                            <p class="text-neutral-400 text-sm leading-relaxed">
                                {{ sport.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits -->
        <section id="benefits" class="py-28 bg-neutral-900 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 space-y-4">
                    <p class="uppercase tracking-[0.3em] text-red-500 text-xs font-black">
                        Beneficios Exclusivos
                    </p>

                    <h2 class="text-3xl md:text-5xl font-black tracking-tight">
                        ¿Por qué elegir Britania Club?
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="(benefit, index) in benefits" :key="benefit.title"
                        class="bg-black/40 border border-white/10 rounded-3xl p-8 transition-all group"
                        :class="index === 1 ? 'hover:border-red-500/40' : 'hover:border-blue-500/40'">
                        <div
                            class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-md group-hover:scale-110 transition-transform text-white"
                            :class="index === 1 ? 'bg-red-600 shadow-red-600/25' : 'bg-blue-600 shadow-blue-600/25'">
                            <i :class="benefit.icon"></i>
                        </div>

                        <h3 class="text-xl font-extrabold mb-3">
                            {{ benefit.title }}
                        </h3>

                        <p class="text-neutral-400 text-sm leading-relaxed">
                            {{ benefit.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery -->
        <section id="gallery" class="py-28 bg-neutral-950 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 space-y-4">
                    <p class="uppercase tracking-[0.3em] text-blue-400 text-xs font-black">
                        Galería
                    </p>

                    <h2 class="text-3xl md:text-5xl font-black tracking-tight">
                        Experiencias en el Club
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(image, index) in gallery" :key="index" class="overflow-hidden rounded-3xl h-80 border border-white/10 group">
                        <img :src="image"
                            class="object-cover h-full w-full group-hover:scale-105 transition-transform duration-500" />
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-28 bg-gradient-to-r from-blue-700 via-neutral-900 to-red-700 text-white relative overflow-hidden border-t border-b border-white/10">
            <div class="absolute -right-10 -bottom-10 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-80 h-80 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="max-w-5xl mx-auto px-6 text-center space-y-8 relative z-10">
                <h2 class="text-3xl md:text-6xl font-black leading-tight tracking-tight">
                    ¿Listo para formar parte de la élite?
                </h2>

                <p class="text-lg md:text-xl text-neutral-300 max-w-2xl mx-auto leading-relaxed">
                    Únete a una comunidad enfocada en el deporte, el bienestar y las mejores experiencias familiares de la región.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                    <button @click="openLink('/torneos')"
                        class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-full font-bold transition-all shadow-xl cursor-pointer flex items-center justify-center gap-2 shadow-blue-600/25">
                        <i class="fas fa-trophy"></i>
                        Inscribirse a Torneos
                    </button>
                    <button @click="openLink('/login')"
                        class="bg-red-600 hover:bg-red-500 text-white px-8 py-4 rounded-full font-bold transition-all shadow-xl cursor-pointer flex items-center justify-center gap-2 shadow-red-600/25">
                        <i class="fas fa-sign-in-alt"></i>
                        Portal Administrativo / Socios
                    </button>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="bg-black py-16">
            <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-3 gap-10">
                <div class="space-y-4">
                    <h3 class="text-2xl font-black">
                        <span class="text-white">Britania </span>
                        <span class="text-red-500">Club</span>
                    </h3>
                    <p class="text-neutral-400 text-sm leading-relaxed">
                        Experiencias premium en deporte, bienestar y convivencia familiar en un entorno exclusivo y moderno.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="font-extrabold text-white text-base">Contacto</h4>
                    <ul class="space-y-3 text-neutral-400 text-sm">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-blue-400 text-xs w-4"></i> 
                            Morelia, Michoacán
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-envelope text-red-400 text-xs w-4"></i> 
                            contacto@britaniaclub.com
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-phone text-blue-400 text-xs w-4"></i> 
                            +52 443 000 0000
                        </li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-extrabold text-white text-base">Síguenos</h4>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-xl bg-neutral-900 border border-white/10 hover:border-blue-500/50 hover:text-blue-450 transition-all flex items-center justify-center"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-neutral-900 border border-white/10 hover:border-red-500/50 hover:text-red-500 transition-all flex items-center justify-center"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-neutral-900 border border-white/10 hover:border-blue-500/50 hover:text-blue-450 transition-all flex items-center justify-center"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
const openLink = (path) => {
    window.open(path, '_blank')
}

const sports = [
    {
        title: 'Tenis',
        description: 'Canchas profesionales de arcilla y torneos para todas las categorías.',
        image: 'https://images.unsplash.com/photo-1542144582-1ba00456b5e3?q=80&w=1974&auto=format&fit=crop',
    },
    {
        title: 'Natación',
        description: 'Alberca techada climatizada diseñada para entrenamiento y recreación.',
        image: 'https://images.unsplash.com/photo-1519315901367-f34ff9154487?q=80&w=1974&auto=format&fit=crop',
    },
    {
        title: 'Gimnasio & Fitness',
        description: 'Área de pesas totalmente equipada y clases dirigidas por entrenadores certificados.',
        image: 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop',
    },
    {
        title: 'Eventos Familiares',
        description: 'Eventos sociales y recreaciones exclusivas diseñadas para todos los miembros.',
        image: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?q=80&w=1974&auto=format&fit=crop',
    },
]

const benefits = [
    {
        icon: 'fas fa-gem',
        title: 'Instalaciones Premium',
        description: 'Acceso total a canchas de tenis, albercas climatizadas, gimnasio equipado y áreas verdes exclusivas.',
    },
    {
        icon: 'fas fa-users',
        title: 'Comunidad Exclusiva',
        description: 'Un entorno seguro y familiar ideal para conectar con otros deportistas y familias.',
    },
    {
        icon: 'fas fa-trophy',
        title: 'Eventos y Torneos',
        description: 'Participación exclusiva en ligas internas, torneos abiertos y eventos especiales todo el año.',
    },
]

const gallery = [
    'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?q=80&w=1974&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1508609349937-5ec4ae374ebf?q=80&w=1974&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1517649763962-0c623066013b?q=80&w=2070&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?q=80&w=1974&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1526676037777-05a232554f77?q=80&w=1974&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=1974&auto=format&fit=crop',
]
</script>
