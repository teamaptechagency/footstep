import fs from 'node:fs';
export function internationalEnabled(filename=process.env.FOOTSTEP_INTERFACE_SETTINGS_FILE){
 if(!filename)return false;let fd;
 try{fd=fs.openSync(filename,fs.constants.O_RDONLY|fs.constants.O_NOFOLLOW);const st=fs.fstatSync(fd);if(!st.isFile()||st.size>4096||st.uid!==process.getuid()||(st.mode&0o002))return false;const data=JSON.parse(fs.readFileSync(fd,'utf8'));return data?.version===1&&data?.enabled===true;}catch{return false;}finally{if(fd!==undefined)fs.closeSync(fd);}
}
