import { ChevronUpIcon, ChevronDownIcon } from "@heroicons/react/16/solid";

export default function TableHeading({name,sort_field=null,sort_direction=null,sortable=true,sortChanged = ()=>{},children}) {

   return (
    <th onClick={e => sortChanged(name)} >
    <div className='px-3 py-2 flex items-center justify-between'>{children}
        
        {sortable &&  <div>
            <ChevronUpIcon className={"w-4 " + (sort_field === name && sort_direction === "asc" ? "text-yellow-400" : "")} />
            <ChevronDownIcon className={"w-4 " + (sort_field === name && sort_direction === "desc" ? "text-yellow-400" : "")} />
        </div>}
       
    </div>
</th>
   )
  

}



