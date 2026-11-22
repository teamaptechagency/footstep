'use client';
import {useState} from 'react';
export default function GuideVideo({id}:{id:string}){
 const [play,setPlay]=useState(false);if(!/^[a-zA-Z0-9_-]{11}$/.test(id))return null;
 return <div className="size-guide-video">{play?<iframe src={`https://www.youtube-nocookie.com/embed/${id}?autoplay=1`} title="পায়ের মাপ নেওয়ার ভিডিও" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowFullScreen/>:<button type="button" onClick={()=>setPlay(true)}><span aria-hidden="true">▶</span><strong>পায়ের মাপ নেওয়ার ভিডিও দেখুন</strong><small>Play video · YouTube</small></button>}</div>;
}
