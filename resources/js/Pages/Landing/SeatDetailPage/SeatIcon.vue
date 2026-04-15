<!-- SeatIcon.vue -->
<template>
    <svg :class="`h-11 w-11 transition-all duration-200 ${color}`" viewBox="0 0 40 40" fill="none"
        xmlns="http://www.w3.org/2000/svg">
        <path
            d="M11.1731 30.8113L9.93555 15.5329C9.93555 13.1072 11.9021 11.1406 14.3277 11.1406H25.8868C28.3125 11.1406 30.279 13.1072 30.279 15.5329L29.0414 30.8113"
            stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path>
        <path
            d="M31.2889 38H9.09895C7.93933 38 7 37.0607 7 35.901V32.5384C7 31.3788 7.93933 30.4395 9.09895 30.4395C16.3512 31.7649 23.7445 31.7909 31.2876 30.4395C32.4472 30.4395 33.3866 31.3788 33.3866 32.5384V35.901C33.3866 37.0607 32.4472 38 31.2876 38H31.2889Z"
            stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path>
        <path
            d="M24.092 7.99748H16.1281C15.0947 7.99748 14.3213 7.15342 14.5304 6.25244L15.2482 3.15841C15.4041 2.48391 16.0724 2 16.8459 2H23.3742C24.1477 2 24.8147 2.48391 24.9719 3.15841L25.6897 6.25244C25.8989 7.15342 25.1254 7.99748 24.092 7.99748Z"
            stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path>
        <path d="M23.5137 7.99805V11.1403" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path>
        <path d="M16.7109 7.99805V11.1403" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path>
        <text v-if="seatNumber" x="50%" y="55%" text-anchor="middle" dy=".3em" font-size="9" font-weight="bold"
            fill="currentColor">
            {{ seatNumber }}
        </text>
    </svg>
</template>

<script setup>
defineProps({
    color: {
        type: String,
        default: "text-blue-500",
    },
    seatNumber: {
        type: [String, Number],
        default: "",
    },
});
</script>
