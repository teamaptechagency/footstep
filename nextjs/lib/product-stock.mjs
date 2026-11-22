/** Admin stock visibility controls presentation; server stock checks remain mandatory. */
export function stockStatus(mode, available, stock) {
 if (!available) return 'বর্তমানে এই size stock-এ নেই।';
 if (mode === 'quantity') return `স্টকে আছে · ${stock} available`;
 if (mode === 'text') return 'স্টকে আছে';
 return null;
}
