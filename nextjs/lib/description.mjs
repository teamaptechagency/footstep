import sanitizeHtml from 'sanitize-html';
export function safeDescription(html,backend){
 return sanitizeHtml(String(html||''),{
  allowedTags:sanitizeHtml.defaults.allowedTags.filter(tag=>!['img','picture','source','svg','image'].includes(tag)),
  allowedAttributes:{...sanitizeHtml.defaults.allowedAttributes,'*':['class','style'],img:['src','alt','width','height','style','loading','decoding'],a:['href','name','target','rel']},
  allowedSchemes:['http','https','mailto','tel'],
  allowedStyles:{'*':{'text-align':[/^(left|right|center|justify)$/],'font-size':[/^\d+(\.\d+)?(px|em|rem|%)$/],'font-weight':[/^(normal|bold|[1-9]00)$/]},img:{width:[/^\d+(\.\d+)?(px|%)$/],height:[/^\d+(\.\d+)?(px|%)$/],float:[/^(left|right|none)$/]}},
  transformTags:{a:sanitizeHtml.simpleTransform('a',{rel:'noopener noreferrer'})}
 });
}
