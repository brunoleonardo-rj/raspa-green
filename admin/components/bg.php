<?php

echo '
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    *::-webkit-scrollbar {
        display: none;
      }
    
    body {
        position: relative;
        background: #0a0a0a;
        background-size: cover;
        background-position: center;
        height: auto;
        min-height: 100vh;
        display: flex;
        width: 100vw;
        justify-content: center;
        align-items: center;
        overflow-y: auto;
        transition: background 2s ease-in-out; 
        animation: pulseGradient 5s infinite alternate ease-in-out;
        box-shadow: inset 0 0 400px 200px rgba(0, 0, 0, 0.5);
        font-family: "Inter", sans-serif;
    }
    
    @keyframes pulseGradient {
        0% { background-position: 0% 0%; }
        50% { background-position: 100% 100%; }
        100% { background-position: 0% 0%; }
    }

    </style>
';

?>