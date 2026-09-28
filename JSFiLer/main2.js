/*window.addEventListener("drag", e=>e.preventDefault());
window.addEventListener("dragstart", e=>e.preventDefault());
window.addEventListener("dragover", e=>e.preventDefault());
window.addEventListener("dragend", e=>e.preventDefault());
window.addEventListener("dragleave", e=>e.preventDefault());

window.addEventListener("dragenter", e=>{
    e.preventDefault();
    console.log('dragenter');
    getElement('loadProg').style.display = '';
    getElement('dragNdrop').style.display = 'block';
});
getElement('dragNdrop').addEventListener("dragleave", e=>{
    e.preventDefault();
    console.log('dragleave');
    getElement('loadProg').style.display = 'none';
    getElement('dragNdrop').style.display = 'none';
});
getElement('dragNdrop').addEventListener("dragend", e=>{
    e.preventDefault();
    console.log('dragend');
    getElement('loadProg').style.display = 'none';
    getElement('dragNdrop').style.display = 'none';
});
document.addEventListener('drop', async (e) => {
    e.preventDefault();
});
*/