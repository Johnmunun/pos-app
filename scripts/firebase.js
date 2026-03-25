// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyAJ4hGRLpS4uKee-yjLoo593AMM6esE52s",
  authDomain: "pos-omni-def24.firebaseapp.com",
  projectId: "pos-omni-def24",
  storageBucket: "pos-omni-def24.firebasestorage.app",
  messagingSenderId: "429216734569",
  appId: "1:429216734569:web:37ad0b9c6c2180c67471f8",
  measurementId: "G-FW36T4WMXB"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);