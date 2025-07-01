<script setup>
const year = new Date().getFullYear()
</script>

<template>
    <footer class="w-full py-4 text-gray-600 bg-gray-100 border-t">
        <div class="container flex flex-col items-center justify-between px-4 mx-auto md:flex-row">
            <p class="text-sm text-center md:text-left">
                © {{ year }} ARTB. All rights reserved.
            </p>
            <p class="mt-2 text-sm text-center md:text-right md:mt-0">
                Developed by
                <a href="#"
                    class="font-semibold text-blue-600 transition duration-300 hover:text-blue-800">
                    Digital Dept ARTB
                </a>
            </p>
        </div>
    </footer>
</template>
