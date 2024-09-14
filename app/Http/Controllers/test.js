/**
 * @param {Function} fn
 * @return {Function}
 */
function memoize(fn) {
    let result
    let cache = []
    return function (...args) {
       let key = JSON.stringify(args)
       if(cache[key])
       {
        return cache[key]
       }
       result = fn(...args)
       cache[key] = result

       return result

    }
}



let callCount = 0;
const memoizedFn = memoize(function (a, b) {
    callCount += 1;
    return a + b;
})
console.log(memoizedFn(2, 3)) // 5

console.log(memoizedFn(2, 3)) // 5
console.log(callCount) // 1 
