<template>
    <div class="lg:w-1/2 p-8 flex items-center justify-center min-h-screen lg:min-h-0">
        <div class="w-full max-w-md">
            <slot name="header" />
            <slot name="form" />
            <slot name="footer" />
        </div>
    </div>
</template>
