'use client';
import {useEffect,useState} from 'react';
export default function Media({src,alt,eager=false}: {src:string;alt:string;eager?:boolean}) {
  const [broken,setBroken]=useState(false);
  useEffect(()=>setBroken(false),[src]);
  return <img src={broken?'/placeholder.svg':src} alt={alt} loading={eager?'eager':'lazy'} decoding="async" onError={()=>setBroken(true)} />;
}
