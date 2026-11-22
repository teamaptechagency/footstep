'use client';
export default function ErrorPage({reset}:{reset:()=>void}){return <div className="container section empty"><h1>Collection সাময়িকভাবে পাওয়া যাচ্ছে না।</h1><p>Laravel server ও database connection চালু আছে কি না পরীক্ষা করুন।</p><button className="button" onClick={()=>reset()}>আবার চেষ্টা করুন</button></div>;}
